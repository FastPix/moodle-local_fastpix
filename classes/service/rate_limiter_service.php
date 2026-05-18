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
 * Service: rate limiter service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

/**
 * Per-IP token-bucket rate limiter backed by MUC area 'rate_limit'.
 * Fail-open: any cache failure returns true so legitimate traffic is never
 * blocked by infrastructure hiccups (rule: fail-closed on auth, fail-open on
 * infra).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter_service {
    /** @var string Cache area. */
    private const CACHE_AREA = 'rate_limit';

    /** @var ?self $instance */
    private static ?self $instance = null;

    /**
     * Constructor.
     */    private function __construct() {
}

    /**
     * Singleton accessor.
     *
     * @return self
     */
public static function instance(): self {
    return self::$instance ??= new self();
}

    /**
     * Reset the singleton (used by tests).
     */    public static function reset(): void {
        self::$instance = null;
}

    /**
     * Allow.
     *
     * @param string $ip
     * @param int $limitperminute
     * @return bool
     */
public function allow(string $ip, int $limitperminute = 60): bool {
    try {
        $cache       = \cache::make('local_fastpix', self::CACHE_AREA);
        $key         = 'rl_' . substr(hash('sha256', $ip), 0, 32);
        $capacity    = (float)$limitperminute;
        $refillrate = $capacity / 60.0;
        $now         = time();

        $bucket = $cache->get($key);
        if (!is_object($bucket) || !isset($bucket->tokens, $bucket->refilled_at)) {
            $bucket = (object)['tokens' => $capacity, 'refilled_at' => $now];
        }

        $elapsed = max(0, $now - (int)$bucket->refilled_at);
        $bucket->tokens = min($capacity, (float)$bucket->tokens + ($elapsed * $refillrate));
        $bucket->refilled_at = $now;

        if ($bucket->tokens >= 1.0) {
            $bucket->tokens -= 1.0;
            $cache->set($key, $bucket);
            return true;
        }

        $cache->set($key, $bucket);
        return false;
    } catch (\Throwable $e) {
        // Fail-open: never block legitimate traffic on cache failure.
        debugging(
            'rate_limiter: cache failure, failing open: ' . $e->getMessage(),
            DEBUG_DEVELOPER
        );
        return true;
    }
}
}
