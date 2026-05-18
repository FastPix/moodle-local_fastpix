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
 * Daily sweep of expired upload sessions. Marks state='orphaned' on rows
 * past their TTL; best-effort DELETE on the FastPix side. Auditability is
 * preserved — rows are not removed.
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

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_orphan_sweeper', 'local_fastpix');
    }

    /**
     * Web service main entry point.
     */    public function execute(): void {
        global $DB;

        $now = time();
        $rows = $DB->get_records_select(
            self::TABLE,
            "state = :state AND expires_at < :now",
            ['state' => 'pending', 'now' => $now],
            'expires_at ASC',
            '*',
            0,
            self::BATCH_SIZE,
        );

        $orphaned = 0;
        foreach ($rows as $row) {
            if (!empty($row->upload_id)) {
                try {
                    \local_fastpix\api\gateway::instance()->delete_media($row->upload_id);
                } catch (\Throwable $e) {
                    mtrace("orphan_sweeper: gateway delete failed for upload_id={$row->upload_id}: "
                    . $e->getMessage());
                }
            }

            $DB->set_field(self::TABLE, 'state', 'orphaned', ['id' => $row->id]);
            $orphaned++;
        }

        mtrace("orphan_sweeper: orphaned {$orphaned} expired session(s)");
}
}
