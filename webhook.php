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
 * Webhook receiver endpoint for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// FastPix webhook endpoint. HMAC-authenticated; no session, no sesskey.
// Thin HTTP wrapper around \local_fastpix\webhook\processor::process().
// Since 2026-05-06 — the verify-then-record-then-enqueue pipeline lives.
// In the processor so the admin "Send test event" button can drive the.
// Same flow without HTTP, and integration tests can do the same.
//
// HTTP-specific concerns stay here: body-size guard, per-IP rate limit,.
// Status-code mapping. Everything else delegates.

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');

// 1. Body size guard (1 MiB cap). CONTENT_LENGTH may be missing on chunked.
// Transfer; treat absence as 0 (we still bail on empty body below).
$contentlength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
if ($contentlength > 1048576) {
    http_response_code(413);
    die();
}

// 2. Read raw body BEFORE any framework parsing.
$rawbody = file_get_contents('php://input');
if ($rawbody === false) {
    http_response_code(400);
    die();
}

// 2a. FastPix validation ping. When the admin configures the webhook URL.
// In FastPix's dashboard, FastPix POSTs a probe to verify reachability.
// Historically the probe was empty or '{}'. As of 2026, the dashboard.
// May also send a small signed JSON body that lacks the id/type fields.
// We treat ANY of these shapes as a validation ping and return 200 so.
// The dashboard accepts the URL; rejecting would mark the URL as.
// Invalid. Validation pings are NOT real events and are NOT inserted.
// Into the ledger. Real events always carry top-level id + type.
$trimmedbody = trim($rawbody);
$isping = ($trimmedbody === '' || $trimmedbody === '{}');
$pingshape = $trimmedbody === '' ? 'empty' : ($trimmedbody === '{}' ? 'curly_braces' : 'unknown');
if (!$isping) {
    $maybe = json_decode($trimmedbody);
    if (
        $maybe instanceof \stdClass
        && (empty($maybe->id) || empty($maybe->type))
    ) {
        $isping = true;
        $pingshape = 'no_id_or_type';
    }
}
if ($isping) {
    // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
    error_log(json_encode([
        'event'         => 'webhook.validation_ping',
        'remote_addr'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'time'          => time(),
        'shape'         => $pingshape,
        'body_first128' => substr($trimmedbody, 0, 128),
    ]));
    http_response_code(200);
    die();
}

// 3. Per-IP rate limit (fail-open on cache failure inside the limiter).
$ip = getremoteaddr() ?: 'unknown';
if (!\local_fastpix\service\rate_limiter_service::instance()->allow($ip)) {
    http_response_code(429);
    die();
}

// 4. Delegate to the processor.
$signature = $_SERVER['HTTP_FASTPIX_SIGNATURE'] ?? '';
$result = \local_fastpix\webhook\processor::process($rawbody, $signature);

switch ($result['result']) {
    case \local_fastpix\webhook\processor::RESULT_ACCEPTED:
    case \local_fastpix\webhook\processor::RESULT_DUPLICATE:
        // Duplicate is success from FastPix's perspective — they already.
        // Got a 200 for this event_id once and our ledger has it.
        http_response_code(200);
        break;

    case \local_fastpix\webhook\processor::RESULT_BAD_SIGNATURE:
        http_response_code(401);
        break;

    case \local_fastpix\webhook\processor::RESULT_MALFORMED_BODY:
        http_response_code(400);
        break;

    case \local_fastpix\webhook\processor::RESULT_DB_ERROR:
    default:
        // Real DB bug surfaced (FK violation, NOT NULL, etc.). Return.
        // 500 so FastPix retries on its normal schedule AND ops sees.
        // It in error logs. Per I1: silently 200ing here would mask.
        // Schema bugs.
        http_response_code(500);
        break;
}

die();
