<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Scheduled task: release ready videos not attached to any activity (Issue 3).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Two-phase reaper for orphaned (unattached) assets.
 *
 *   Phase 1 — WARN:    a ready asset with zero references that has been
 *                      unattached past (grace - lead) is warned: the owner gets
 *                      a Moodle message and unattached_warned_at is stamped.
 *   Phase 2 — RELEASE: a warned asset whose lead time has since elapsed and
 *                      which is still unattached is soft-deleted via the
 *                      existing path (the 7-day purge then removes it on
 *                      FastPix). Soft-delete therefore never happens at the
 *                      same moment as the warning — the owner always has the
 *                      lead window to attach the video and rescue it.
 *
 * Attaching a consumer (asset_service::add_reference) clears the warning and
 * removes the asset from both phases.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class release_unattached_assets extends \core\task\scheduled_task {
    /** @var string Asset table. */
    private const ASSET_TABLE = 'local_fastpix_asset';
    /** @var int Batch size per phase. */
    private const BATCH_SIZE = 500;
    /** @var int Fallback grace when the admin setting is unset/invalid (7d). */
    private const DEFAULT_GRACE_SECONDS = WEEKSECS;
    /** @var int Fallback warning lead when unset/invalid (2d). */
    private const DEFAULT_LEAD_SECONDS = 2 * DAYSECS;

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_release_unattached_assets', 'local_fastpix');
    }

    /**
     * Warn, then (after the lead window) release, unattached ready assets.
     */
    public function execute(): void {
        global $DB;

        $grace = (int)get_config('local_fastpix', 'unattached_grace');
        if ($grace <= 0) {
            $grace = self::DEFAULT_GRACE_SECONDS;
        }
        $lead = (int)get_config('local_fastpix', 'unattached_warning_lead');
        if ($lead <= 0) {
            $lead = self::DEFAULT_LEAD_SECONDS;
        }
        // The warning must precede release, so the lead cannot exceed the grace.
        if ($lead > $grace) {
            $lead = $grace;
        }
        // Auto-release is OFF by default: a video can be embedded via
        // filter_fastpix / tinymce_fastpix in arbitrary content and carry no
        // asset_ref row, so until those consumers report usage (touch_seen)
        // the task only NOTIFIES — it never deletes.
        $autorelease = (bool)get_config('local_fastpix', 'auto_release_enabled');
        $now = time();
        $seencutoff = $now - $grace;

        // Phase 1 — WARN. Ready assets with no reference AND not rendered within
        // the grace window (the filter heartbeat protects embedded videos),
        // that reached (grace - lead) and are not yet warned.
        $warned = 0;
        $towarn = $DB->get_records_sql(
            "SELECT a.id, a.fastpix_id, a.title, a.owner_userid
               FROM {" . self::ASSET_TABLE . "} a
          LEFT JOIN {local_fastpix_asset_ref} r ON r.asset_id = a.id
              WHERE a.status = :status
                AND a.deleted_at IS NULL
                AND a.unattached_warned_at IS NULL
                AND a.timecreated < :cutoff
                AND (a.last_seen_at IS NULL OR a.last_seen_at < :seencutoff)
                AND r.id IS NULL",
            ['status' => 'ready', 'cutoff' => $now - ($grace - $lead), 'seencutoff' => $seencutoff],
            0,
            self::BATCH_SIZE,
        );
        foreach ($towarn as $row) {
            $this->notify_owner($row, $lead, $autorelease);
            $DB->set_field(self::ASSET_TABLE, 'unattached_warned_at', $now, ['id' => (int)$row->id]);
            $warned++;
        }

        // Phase 2 — RELEASE. Only when the admin has enabled auto-release.
        // Warned assets past the lead window, still unattached and unseen.
        $released = 0;
        if ($autorelease) {
            $torelease = $DB->get_records_sql(
                "SELECT a.id
                   FROM {" . self::ASSET_TABLE . "} a
              LEFT JOIN {local_fastpix_asset_ref} r ON r.asset_id = a.id
                  WHERE a.status = :status
                    AND a.deleted_at IS NULL
                    AND a.unattached_warned_at IS NOT NULL
                    AND a.unattached_warned_at < :cutoff
                    AND (a.last_seen_at IS NULL OR a.last_seen_at < :seencutoff)
                    AND r.id IS NULL",
                ['status' => 'ready', 'cutoff' => $now - $lead, 'seencutoff' => $seencutoff],
                0,
                self::BATCH_SIZE,
            );
            foreach ($torelease as $row) {
                \local_fastpix\service\asset_service::soft_delete((int)$row->id);
                $released++;
            }
        }

        mtrace("release_unattached_assets: warned {$warned}, released {$released}"
            . ($autorelease ? '' : ' (auto-release disabled — notify only)'));
    }

    /**
     * Notify the owning user that their unattached video is unused. When
     * auto-release is enabled the message states the removal countdown; when
     * disabled it is informational only. Sentinel owners (0) are skipped.
     *
     * @param \stdClass $row Asset row with owner_userid + title.
     * @param int $lead Seconds of lead before release.
     * @param bool $autorelease Whether auto-release is enabled.
     */
    private function notify_owner(\stdClass $row, int $lead, bool $autorelease): void {
        $userid = (int)$row->owner_userid;
        if ($userid <= 0) {
            return;
        }
        $a = (object)[
            'title' => (string)$row->title,
            'days'  => max(1, (int)round($lead / DAYSECS)),
        ];
        $bodykey = $autorelease ? 'message_unattached_body' : 'message_unattached_review';
        $message = new \core\message\message();
        $message->component         = 'local_fastpix';
        $message->name              = 'unattached_asset_released';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = $userid;
        $message->subject           = get_string('message_unattached_subject', 'local_fastpix');
        $message->fullmessage       = get_string($bodykey, 'local_fastpix', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = text_to_html($message->fullmessage);
        $message->smallmessage      = $message->subject;
        $message->notification      = 1;
        message_send($message);
    }
}
