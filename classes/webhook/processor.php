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
 * Webhook component: processor.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\webhook;

/**
 * Verify-record-enqueue pipeline extracted from webhook.php.
 * Both the HTTP endpoint (webhook.php) and the admin "Send test event"
 * button (\local_fastpix\external\send_test_event) drive the same flow
 * through this processor so the projection contract is exercised by
 * both surfaces.
 * Inputs:
 *   - $rawbody: the bytes of the POST body (must be read via
 *     file_get_contents('php://input') BEFORE any framework parsing).
 *   - $signatureheader: FastPix-Signature header value.
 * Outputs (array shape):
 *   [
 *     'result'    => RESULT_*,                  (one of the constants below)
 *     'ledger_id' => int|null,                  (row id when ACCEPTED/DUPLICATE)
 *     'event_id'  => string|null,               (provider_event_id when known)
 *     'error'     => string|null,               (human-readable reason on rejection)
 *   ]
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {
    /** @var string Result accepted. */
    public const RESULT_ACCEPTED       = 'accepted';
    /** @var string Result duplicate. */
    public const RESULT_DUPLICATE      = 'duplicate';
    /** @var string Result bad signature. */
    public const RESULT_BAD_SIGNATURE  = 'bad_signature';
    /** @var string Result malformed body. */
    public const RESULT_MALFORMED_BODY = 'malformed_body';
    /** @var string Result db error. */
    public const RESULT_DB_ERROR       = 'db_error';

    /** @var string Ledger table. */
    private const LEDGER_TABLE = 'local_fastpix_webhook_event';

    /**
     * Process.
     *
     * @param string $rawbody
     * @param string $signatureheader
     * @return array
     */
    public static function process(string $rawbody, string $signatureheader): array {
        // 1. Signature verification (rule S3 — hash_equals via verifier).
        if (!verifier::instance()->verify($rawbody, $signatureheader)) {
            return self::result(self::RESULT_BAD_SIGNATURE, null, null, 'signature verification failed');
        }

        // 2. Parse + validate JSON. A decode failure and a missing id/type are
        // both RESULT_MALFORMED_BODY; keep their distinct reasons and event_id.
        $event     = json_decode($rawbody);
        $valid     = $event instanceof \stdClass;
        $eventid   = ($valid && isset($event->id)) ? (string)$event->id : '';
        $eventtype = ($valid && isset($event->type)) ? (string)$event->type : '';
        if (!$valid || $eventid === '' || $eventtype === '') {
            return self::result(
                self::RESULT_MALFORMED_BODY,
                null,
                $eventid !== '' ? $eventid : null,
                $valid ? 'missing required field id/type' : 'JSON decode failed'
            );
        }

        // 3-4. Idempotent ledger insert + enqueue projection.
        $eventcreatedat = isset($event->occurredAt) ? (int)$event->occurredAt : time();
        return self::insert_and_enqueue($eventid, $eventtype, $eventcreatedat, $rawbody, $signatureheader);
    }

    /**
     * Idempotent ledger insert and adhoc-projection enqueue. UNIQUE on
     * provider_event_id catches duplicates as dml_write_exception — a
     * duplicate is success (W1).
     *
     * @param string $eventid
     * @param string $eventtype
     * @param int $eventcreatedat
     * @param string $rawbody
     * @param string $signatureheader
     * @return array
     */
    private static function insert_and_enqueue(
        string $eventid,
        string $eventtype,
        int $eventcreatedat,
        string $rawbody,
        string $signatureheader
    ): array {
        global $DB;

        try {
            $transaction = $DB->start_delegated_transaction();

            try {
                $ledgerid = $DB->insert_record(self::LEDGER_TABLE, (object)[
                    'provider_event_id'     => $eventid,
                    'event_type'            => $eventtype,
                    'event_created_at'      => $eventcreatedat,
                    'payload'               => $rawbody,
                    'signature'             => $signatureheader,
                    'status'                => 'pending',
                    'received_at'           => time(),
                    'processing_latency_ms' => 0,
                ]);
            } catch (\dml_write_exception $e) {
                // Duplicate — UNIQUE constraint hit. W1: duplicate is success.
                $transaction->allow_commit();
                $existing = $DB->get_record(
                    self::LEDGER_TABLE,
                    ['provider_event_id' => $eventid],
                    'id'
                );
                return self::result(
                    self::RESULT_DUPLICATE,
                    $existing ? (int)$existing->id : null,
                    $eventid,
                    null
                );
            }

            // Enqueue adhoc task for asynchronous projection.
            $task = new \local_fastpix\task\process_webhook();
            $task->set_custom_data((object)['provider_event_id' => $eventid]);
            \core\task\manager::queue_adhoc_task($task);

            $transaction->allow_commit();

            return self::result(self::RESULT_ACCEPTED, (int)$ledgerid, $eventid, null);
        } catch (\Throwable $e) {
            return self::result(self::RESULT_DB_ERROR, null, $eventid, $e->getMessage());
        }
    }

    /**
     * Build the processor result array.
     *
     * @param string $result One of the RESULT_* constants.
     * @param ?int $ledgerid Ledger row id when ACCEPTED/DUPLICATE.
     * @param ?string $eventid provider_event_id when known.
     * @param ?string $error Human-readable reason on rejection.
     * @return array
     */
    private static function result(string $result, ?int $ledgerid, ?string $eventid, ?string $error): array {
        return [
            'result'    => $result,
            'ledger_id' => $ledgerid,
            'event_id'  => $eventid,
            'error'     => $error,
        ];
    }
}
