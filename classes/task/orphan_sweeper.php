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
 * Scheduled or adhoc task: orphan sweeper.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Daily sweep of stale upload sessions: rows still 'pending' (never linked to
 * an asset) older than the configurable TTL (admin setting
 * orphaned_session_ttl, default 24h). Cancels the FastPix-side upload
 * best-effort, then deletes the local row so abandoned sessions don't
 * accumulate. Linked sessions (state != 'pending') are never touched.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class orphan_sweeper extends \core\task\scheduled_task {
    /** @var string Table. */
    private const TABLE = 'local_fastpix_upload_session';
    /** @var int Batch size. */
    private const BATCH_SIZE = 500;
    /** @var int Fallback TTL when the admin setting is unset/invalid (24h). */
    private const DEFAULT_TTL_SECONDS = DAYSECS;

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_orphan_sweeper', 'local_fastpix');
    }

    /**
     * Delete stale, never-progressed upload sessions past the TTL.
     */
    public function execute(): void {
        global $DB;

        $ttl = (int)get_config('local_fastpix', 'orphaned_session_ttl');
        if ($ttl <= 0) {
            $ttl = self::DEFAULT_TTL_SECONDS;
        }
        $cutoff = time() - $ttl;

        $rows = $DB->get_records_select(
            self::TABLE,
            "state = :state AND timecreated < :cutoff",
            ['state' => 'pending', 'cutoff' => $cutoff],
            'timecreated ASC',
            '*',
            0,
            self::BATCH_SIZE,
        );

        $deleted = 0;
        foreach ($rows as $row) {
            // Best-effort cancel on the FastPix side so the abandoned upload
            // doesn't linger (and cost) on the provider. Failures are logged
            // and do not block the local cleanup.
            if (!empty($row->upload_id)) {
                try {
                    \local_fastpix\api\gateway::instance()->delete_media($row->upload_id);
                } catch (\Throwable $e) {
                    mtrace("orphan_sweeper: gateway delete failed for upload_id={$row->upload_id}: "
                    . $e->getMessage());
                }
            }

            $DB->delete_records(self::TABLE, ['id' => $row->id]);
            $deleted++;
        }

        mtrace("orphan_sweeper: deleted {$deleted} stale pending session(s) older than {$ttl}s");
    }
}
