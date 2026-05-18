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
 * Scheduled or adhoc task: process webhook.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Adhoc task: project a single verified webhook event onto its asset row.
 * Enqueued by webhook.php after signature verification + ledger insert.
 * Custom data: ['provider_event_id' => <FastPix event UUID>].
 * Failures (other than malformed-at-enqueue) propagate so Moodle's adhoc-task
 * retry/backoff mechanism reschedules — including lock_acquisition_failed.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_webhook extends \core\task\adhoc_task {
    /** @var string Ledger table. */
    private const LEDGER_TABLE = 'local_fastpix_webhook_event';

    /**
     * Web service main entry point.
     */    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $eventid = (string)($data->provider_event_id ?? '');

        if ($eventid === '') {
            mtrace('process_webhook: missing provider_event_id in custom_data; dropping');
            return;
        }

        $row = $DB->get_record(self::LEDGER_TABLE, ['provider_event_id' => $eventid]);
        if ($row === false) {
            mtrace("process_webhook: ledger row not found for {$eventid}; dropping");
            return;
        }

        $event = json_decode((string)$row->payload);
        if (!($event instanceof \stdClass)) {
            mtrace("process_webhook: malformed payload for {$eventid}; dropping");
            $DB->set_field(self::LEDGER_TABLE, 'status', 'malformed', ['id' => $row->id]);
            return;
        }

        $eventtype = (string)($event->type ?? '');
        $projector  = new \local_fastpix\webhook\projector();

        // Lock_acquisition_failed and any other exception bubble up so the.
        // Adhoc-task system retries with backoff.
        $projector->project($event);

        $DB->update_record(self::LEDGER_TABLE, (object)[
            'id'                    => $row->id,
            'status'                => 'processed',
            'processing_latency_ms' => max(0, (int)((microtime(true) * 1000) - ((int)$row->received_at * 1000))),
        ]);

        mtrace("process_webhook: processed event_id={$eventid} type={$eventtype}");
}
}
