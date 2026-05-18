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
 * External (web service) function: send test event.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * Admin "Send Test Event" web service.
 * Fires a synthetic FastPix-shaped video.media.created event into the
 * local processor (verifier → ledger insert → adhoc-task enqueue) so the
 * admin can validate end-to-end webhook plumbing without touching FastPix.
 * Capability: local/fastpix:configurecredentials.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_test_event extends \core_external\external_api {
    /**
     * Web service parameter spec.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([]);
    }

    /**
     * Execute the test-event web service.
     *
     * @return array{
     *     success: bool, ledger_id: int, result: string,
     *     event_id: string, errors: array<int, string>
     * }
     */
    public static function execute(): array {
        $context = \context_system::instance();
        self::validate_context($context);
        require_login(null, false);
        require_sesskey();
        require_capability('local/fastpix:configurecredentials', $context);

        $errors = [];

        $secret = (string)get_config('local_fastpix', 'webhook_secret_current');
        if (strlen($secret) < 32) {
            return [
            'success'   => false,
            'ledger_id' => 0,
            'result'    => 'webhook_secret_unconfigured',
            'event_id'  => '',
            'errors'    => ['webhook_secret_current is empty or below 32-byte minimum'],
            ];
        }

        $eventid = 'test-event-' . time() . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $payload = json_encode([
        'id'         => $eventid,
        'type'       => 'video.media.created',
        'occurredAt' => time(),
        'object'     => ['type' => 'video.media', 'id' => 'test-asset-' . substr(bin2hex(random_bytes(6)), 0, 12)],
        'data'       => (object)['title' => 'Synthetic admin test event', 'status' => 'created'],
        ], JSON_UNESCAPED_SLASHES);

        // Sign using FastPix canonical shape: base64(hmac(base64_decode(secret), body)).
        $decodedsecret = base64_decode($secret, true);
        if ($decodedsecret === false || $decodedsecret === '') {
            // Fall back to raw-string keying so admins with a raw-string.
            // (non-base64) secret still get a meaningful test.
            $decodedsecret = $secret;
        }
        $signature = base64_encode(hash_hmac('sha256', $payload, $decodedsecret, true));

        $result = \local_fastpix\webhook\processor::process($payload, $signature);

        $success = in_array(
            $result['result'],
            [
            \local_fastpix\webhook\processor::RESULT_ACCEPTED,
            \local_fastpix\webhook\processor::RESULT_DUPLICATE,
            ],
            true,
        );
        if (!$success && !empty($result['error'])) {
            $errors[] = (string)$result['error'];
        }

        return [
        'success'   => $success,
        'ledger_id' => (int)($result['ledger_id'] ?? 0),
        'result'    => (string)$result['result'],
        'event_id'  => $eventid,
        'errors'    => $errors,
        ];
    }

    /**
     * Web service return spec.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'success' => new \core_external\external_value(
                PARAM_BOOL,
                'true if the synthetic event landed in the ledger'
            ),
            'ledger_id' => new \core_external\external_value(
                PARAM_INT,
                'ledger row id, or 0 on failure'
            ),
            'result' => new \core_external\external_value(
                PARAM_TEXT,
                'processor result token'
            ),
            'event_id' => new \core_external\external_value(
                PARAM_TEXT,
                'provider_event_id of the synthetic event'
            ),
            'errors' => new \core_external\external_multiple_structure(
                new \core_external\external_value(PARAM_TEXT, 'error message')
            ),
        ]);
    }
}
