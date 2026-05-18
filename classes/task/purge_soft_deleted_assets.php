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
 * Scheduled or adhoc task: purge soft deleted assets.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

use local_fastpix\util\cache_keys;

/**
 * Daily hard-purge of assets that have been soft-deleted for ≥ 7 days
 * (rule W10).
 * Soft-delete is the user-facing action: `asset_service::soft_delete()`
 * stamps `deleted_at` on the row and invalidates the asset cache. After
 * a 7-day grace window the row is hard-deleted by this task — caption
 * rows in `local_fastpix_track` are removed in the same loop because
 * the FK is declared without ON DELETE CASCADE.
 * Distinct from `asset_cleanup`, which handles a different lifecycle:
 * GDPR-pending rows where the local delete succeeded but the FastPix
 * delete failed (gdpr_delete_pending_at).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_soft_deleted_assets extends \core\task\scheduled_task {
    /** @var string Asset table. */
    private const ASSET_TABLE = 'local_fastpix_asset';
    /** @var string Track table. */
    private const TRACK_TABLE = 'local_fastpix_track';

    /** @var int Retention seconds. */
    private const RETENTION_SECONDS = 604800; // 7 Days (rule W10).
    /** @var int Batch size. */
    private const BATCH_SIZE = 500;

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_purge_soft_deleted_assets', 'local_fastpix');
    }

    /**
     * Web service main entry point.
     */    public function execute(): void {
        global $DB;

        $startms = (int)(microtime(true) * 1000);
        $cutoff = time() - self::RETENTION_SECONDS;

        $rows = $DB->get_records_select(
            self::ASSET_TABLE,
            'deleted_at IS NOT NULL AND deleted_at < :cutoff',
            ['cutoff' => $cutoff],
            'deleted_at ASC',
            'id, fastpix_id, playback_id',
            0,
            self::BATCH_SIZE,
        );

        $purged = 0;
        $cache = \cache::make('local_fastpix', 'asset');

        foreach ($rows as $row) {
            try {
                $DB->delete_records(self::TRACK_TABLE, ['asset_id' => (int)$row->id]);
                $DB->delete_records(self::ASSET_TABLE, ['id' => (int)$row->id]);

                $cache->delete(cache_keys::fastpix((string)$row->fastpix_id));
                if (!empty($row->playback_id)) {
                    $cache->delete(cache_keys::playback((string)$row->playback_id));
                }
                $purged++;
            } catch (\Throwable $e) {
                mtrace("purge_soft_deleted_assets: failed to purge id={$row->id}: " . $e->getMessage());
            }
        }

        $remaining = $DB->count_records_select(
            self::ASSET_TABLE,
            'deleted_at IS NOT NULL AND deleted_at < :cutoff',
            ['cutoff' => $cutoff],
        );
        $elapsedms = (int)(microtime(true) * 1000) - $startms;

        mtrace(json_encode([
            'event'           => 'task.purge_soft_deleted_assets',
            'count_purged'    => $purged,
            'count_remaining' => $remaining,
            'elapsed_ms'      => $elapsedms,
            'batch_size'      => self::BATCH_SIZE,
        ]));
}
}
