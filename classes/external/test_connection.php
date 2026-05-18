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
                $error = 'health_probe returned false';
            }
        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage();
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
