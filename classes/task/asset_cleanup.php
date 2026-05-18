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
 * Scheduled or adhoc task: asset cleanup.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Hard-deletes assets past their GDPR retention window (90 days from when
 * gdpr_delete_pending_at was set). Best-effort FastPix-side delete; local
 * row removal is authoritative regardless.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asset_cleanup extends \core\task\scheduled_task {
    /** @var string Table. */
    private const TABLE = 'local_fastpix_asset';
    /** @var int Retention seconds. */
    private const RETENTION_SECONDS = 7776000; // 90 Days.
    /** @var int Batch size. */
    private const BATCH_SIZE = 200;

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_asset_cleanup', 'local_fastpix');
    }

    /**
     * Web service main entry point.
     */    public function execute(): void {
        global $DB;

        $cutoff = time() - self::RETENTION_SECONDS;
        $rows = $DB->get_records_select(
            self::TABLE,
            'gdpr_delete_pending_at IS NOT NULL AND gdpr_delete_pending_at < :cutoff',
            ['cutoff' => $cutoff],
            'gdpr_delete_pending_at ASC',
            '*',
            0,
            self::BATCH_SIZE,
        );

        $deleted = 0;
        foreach ($rows as $row) {
            try {
                if (!empty($row->fastpix_id)) {
                    try {
                        \local_fastpix\api\gateway::instance()->delete_media((string)$row->fastpix_id);
                    } catch (\Throwable $e) {
                        mtrace("asset_cleanup: gateway delete failed for {$row->fastpix_id}: "
                        . $e->getMessage());
                    }
                }

                $DB->delete_records(self::TABLE, ['id' => (int)$row->id]);
                $this->invalidate_cache((string)$row->fastpix_id, $row->playback_id ?? null);
                $deleted++;
            } catch (\Throwable $e) {
                // Per-row failure must not abort the batch.
                mtrace("asset_cleanup: row id={$row->id} failed: " . $e->getMessage());
            }
        }

        mtrace("asset_cleanup: hard-deleted {$deleted} asset(s) past 90-day GDPR retention");
}

    /**
     * Invalidate cache.
     *
     * @param string $fastpixid
     * @param ?string $playbackid
     */
private function invalidate_cache(string $fastpixid, ?string $playbackid): void {
    $cache = \cache::make('local_fastpix', 'asset');
    if ($fastpixid !== '') {
        $cache->delete('fp_' . substr(hash('sha256', $fastpixid), 0, 32));
    }
    if (!empty($playbackid)) {
        $cache->delete('pb_' . substr(hash('sha256', $playbackid), 0, 32));
    }
}
}
