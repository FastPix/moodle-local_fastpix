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
 * External (web service) function: test connection.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * Admin "Test Connection" web service.
 * Wraps gateway::health_probe() (the existing read-only probe used by the
 * scheduled-task suite + the public /health.php endpoint) so the admin
 * settings page can drive it via AJAX on demand. Returns latency and a
 * human-readable error so the operator immediately knows whether the
 * configured credentials reach FastPix.
 * Capability: local/fastpix:configurecredentials (Path A per ADR-014 +
 * v1.0 review M1 finding).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_connection extends \core_external\external_api {
    /**
     * Web service parameter spec.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([]);
    }

    /**
     * Execute.
     *
     * @return array{success: bool, latency_ms: int, error: string|null}
     */
    public static function execute(): array {
        $context = \context_system::instance();
        self::validate_context($context);
        require_login(null, false);
        require_sesskey();
        require_capability('local/fastpix:configurecredentials', $context);

        $start = microtime(true);
        $success = false;
        $error = null;
        try {
            $success = (bool)\local_fastpix\api\gateway::instance()->health_probe();
            if (!$success) {
                $error = get_string('test_connection_probe_failed', 'local_fastpix');
            }
        } catch (\Throwable $e) {
            // health_probe() is documented never to throw; this is defensive.
            // Log only the exception class server-side (the message may carry an
            // upstream response body — rule S2) and return a safe, localised
            // message to the caller rather than the internal class/message.
            debugging('local_fastpix test_connection: unexpected ' . get_class($e), DEBUG_DEVELOPER);
            $error = get_string('test_connection_probe_failed', 'local_fastpix');
        }
        $latencyms = (int)((microtime(true) - $start) * 1000);

        return [
        'success'    => $success,
        'latency_ms' => $latencyms,
        'error'      => $error,
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
                'true on a 2xx response from the gateway'
            ),
            'latency_ms' => new \core_external\external_value(
                PARAM_INT,
                'wall-clock time spent in the probe'
            ),
            'error' => new \core_external\external_value(
                PARAM_TEXT,
                'human-readable failure reason',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
        ]);
    }
}
