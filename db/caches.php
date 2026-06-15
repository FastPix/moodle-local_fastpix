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
 * MUC cache definitions for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$definitions = [
    'asset' => [
        'mode'                   => cache_store::MODE_APPLICATION,
        'simplekeys'             => true,
        'simpledata'             => false,
        'persistent'             => true,
        'staticacceleration'     => true,
        'staticaccelerationsize' => 100,
        'ttl'                    => 60,
    ],
    'rate_limit' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl'        => 60,
    ],
    // Circuit breaker state. CRITICAL: must be in MUC (shared store, e.g. Redis).
    // For multi-FPM correctness. Document this requirement in README.
    'circuit_breaker' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl'        => 60,
    ],
    'upload_dedup' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl'        => 60,
    ],
    // Throttle for asset_service::touch_seen() — at most one last_seen_at write
    // per asset per TTL window, so the filter's render heartbeat doesn't hammer
    // the DB on every page view.
    'seen_heartbeat' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl'        => 3600,
    ],
    // Short-lived cache of the public health endpoint's probe result. The
    // endpoint is unauthenticated; caching bounds it to roughly one outbound
    // FastPix probe per TTL window regardless of how many anonymous callers
    // (or IPs) hit it, so it cannot be used to amplify load onto FastPix.
    'health' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl'        => 30,
    ],
];
