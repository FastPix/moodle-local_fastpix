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
 * Health-check component: runner.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\health;

/**
 * Health-endpoint logic, extracted for testability.
 * The thin script `health.php` at the plugin root delegates here so unit
 * tests can exercise the rate-limit, probe, and error-recovery paths
 * without driving an HTTP request.
 * NEVER throws. Any exception from the gateway, the rate limiter, or
 * anything downstream is converted to a 503 response. The endpoint must
 * not 500 — that would prevent ops from distinguishing a hard failure
 * from a slow probe.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class runner {
    /**
     * Rate-limit cap per IP per minute.
     */    public const RATE_LIMIT_PER_MIN = 30;

    /**
     * Run the health check for one request.
     *
     * @param string $clientip Source IP for rate-limit keying.
     * @return array{http_code: int, body: array<string, mixed>}
     */
    public static function run(string $clientip): array {
        try {
            $limiter = \local_fastpix\service\rate_limiter_service::instance();
            if (!$limiter->allow($clientip, self::RATE_LIMIT_PER_MIN)) {
                return self::response(429, 'rate_limited', null, 0);
            }

            $start = microtime(true);
            $reachable = \local_fastpix\api\gateway::instance()->health_probe();
            $latencyms = (int)((microtime(true) - $start) * 1000);

            return self::response(
                $reachable ? 200 : 503,
                $reachable ? 'ok' : 'degraded',
                $reachable,
                $latencyms,
            );
        } catch (\Throwable $e) {
            // Defensive — health_probe is documented as never-throws, but.
            // If anything upstream (rate limiter, MUC, gateway construction).
            // Does, swallow it and report degraded. The exception class.
            // Name is logged via debugging() for ops visibility; the.
            // Message body is not (could contain sensitive context).
            debugging('local_fastpix health endpoint: ' . get_class($e), DEBUG_DEVELOPER);
            return self::response(503, 'error', false, 0);
        }
    }

    /**
     * Response.
     *
     * @return array{http_code: int, body: array<string, mixed>}
     * @param int $httpcode
     * @param string $status
     * @param ?bool $reachable
     * @param int $latencyms
     * @return array
     */
    private static function response(int $httpcode, string $status, ?bool $reachable, int $latencyms): array {
        return [
            'http_code' => $httpcode,
            'body' => [
                'status'            => $status,
                'fastpix_reachable' => $reachable,
                'latency_ms'        => $latencyms,
                'timestamp'         => time(),
            ],
        ];
    }
}
