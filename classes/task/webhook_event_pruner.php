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
 * Scheduled or adhoc task: webhook event pruner.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Daily prune of processed webhook ledger rows older than 90 days.
 * Per rule W9, the ledger retains 90 days of processed events for
 * forensics and replay. Only rows with status='processed' are eligible —
 * anything still pending or marked malformed is preserved indefinitely
 * for investigation. The privacy provider declares this retention so
 * admins see it on the privacy registry page.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook_event_pruner extends \core\task\scheduled_task {
    /** @var string Table. */
    private const TABLE = 'local_fastpix_webhook_event';
    /** @var int Retention seconds. */
    private const RETENTION_SECONDS = 7776000; // 90 Days (rule W9).

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_webhook_event_pruner', 'local_fastpix');
    }

    /**
     * Web service main entry point.
     */    public function execute(): void {
        global $DB;

        $cutoff = time() - self::RETENTION_SECONDS;

        try {
            $count = $DB->count_records_select(
                self::TABLE,
                "status = :status AND received_at < :cutoff",
                ['status' => 'processed', 'cutoff' => $cutoff],
            );

            $DB->delete_records_select(
                self::TABLE,
                "status = :status AND received_at < :cutoff",
                ['status' => 'processed', 'cutoff' => $cutoff],
            );

            mtrace("webhook_event_pruner: deleted {$count} processed event(s) older than 90 days");
        } catch (\Throwable $e) {
            mtrace('webhook_event_pruner: prune failed: ' . $e->getMessage());
        }
}
}
