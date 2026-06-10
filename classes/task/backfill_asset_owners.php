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
 * Adhoc task: backfill asset owner_userid from the upload_session ledger.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * One-off backfill: assets created before the projector recovered ownership
 * carry owner_userid=0 (the sentinel). The uploader's real id lives on the
 * matching local_fastpix_upload_session row. This task recovers it for the
 * historical backlog so asset_service::list_for_owner() (the tiny_fastpix
 * picker) returns those rows. Queued from db/upgrade.php.
 *
 * Batched and time-boxed so a large asset table never blocks cron; re-queues
 * itself while recoverable sentinel rows remain. The guarded write
 * (owner_userid=0 condition) keeps it idempotent under reruns.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backfill_asset_owners extends \core\task\adhoc_task {
    /** @var int Wall-clock budget per run. */
    private const TIME_LIMIT_SECONDS = 60;
    /** @var int Max rows fetched per run. */
    private const BATCH_LIMIT = 1000;
    /** @var string Recoverable-rows predicate (shared by scan + remaining check). */
    private const RECOVERABLE_SQL =
        "FROM {local_fastpix_asset} a
         JOIN {local_fastpix_upload_session} s ON s.fastpix_id = a.fastpix_id
        WHERE a.owner_userid = 0 AND s.userid > 0";

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_backfill_asset_owners', 'local_fastpix');
    }

    /**
     * Recover owner_userid for sentinel-owned assets from the session ledger.
     */
    public function execute(): void {
        global $DB;

        $started = time();
        $updated = 0;

        $sql = "SELECT a.id AS assetid, a.fastpix_id, a.playback_id, s.userid "
            . self::RECOVERABLE_SQL . " ORDER BY a.id";
        $rs = $DB->get_recordset_sql($sql, null, 0, self::BATCH_LIMIT);
        $cache = \cache::make('local_fastpix', 'asset');

        foreach ($rs as $r) {
            // Guarded set: idempotent and owner-safe — never overwrites a
            // non-sentinel owner, even if a concurrent run got there first.
            $DB->set_field(
                'local_fastpix_asset',
                'owner_userid',
                (int)$r->userid,
                ['id' => (int)$r->assetid, 'owner_userid' => 0]
            );
            $cache->delete(\local_fastpix\util\cache_keys::fastpix((string)$r->fastpix_id));
            if (!empty($r->playback_id)) {
                $cache->delete(\local_fastpix\util\cache_keys::playback((string)$r->playback_id));
            }
            $updated++;

            if (time() - $started >= self::TIME_LIMIT_SECONDS) {
                break;
            }
        }
        $rs->close();

        mtrace("backfill_asset_owners: set owner on {$updated} asset(s)");

        // Re-queue only when we made progress and recoverable rows remain —
        // the progress guard prevents an infinite loop on irrecoverable rows.
        if (
            $updated > 0
            && $DB->record_exists_sql("SELECT 1 " . self::RECOVERABLE_SQL)
        ) {
            \core\task\manager::queue_adhoc_task(new self());
        }
    }
}
