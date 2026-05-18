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
 * Webhook component: verifier.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\webhook;

/**
 * Webhook signature verifier (HMAC SHA-256, dual-secret rotation).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verifier {
    /** @var string Hmac algo. */
    private const HMAC_ALGO        = 'sha256';
    /** @var int Rotation window. */
    private const ROTATION_WINDOW  = 1800; // 30 Minutes.

    // Minimum acceptable length (bytes) for the configured webhook secret.
    /** @var int Min secret bytes. */
    private const MIN_SECRET_BYTES = 32;

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
     * Verify a FastPix webhook signature.
     * Returns true if the signature matches the current secret, or matches the
     * previous secret within the 30-minute rotation window. Returns false on
     * any failure — never throws (rule S7).
     *
     * @param string $rawbody
     * @param string $signatureheader
     * @return bool
     */
public function verify(string $rawbody, string $signatureheader): bool {
    if (strlen($signatureheader) < 1 || strlen($rawbody) < 1) {
        debugging('webhook signature verify: empty body or signature', DEBUG_DEVELOPER);
        return false;
    }

    $current = (string)get_config('local_fastpix', 'webhook_secret_current');
    if (strlen($current) < 1) {
        debugging('webhook signature verify: current secret not configured', DEBUG_DEVELOPER);
        return false;
    }
    if (strlen($current) < self::MIN_SECRET_BYTES) {
        $this->log_short_secret('current', strlen($current));
        return false;
    }

    if ($this->matches_either_format($rawbody, $current, $signatureheader)) {
        return true;
    }

    $previous = (string)get_config('local_fastpix', 'webhook_secret_previous');
    $rotatedat = (int)get_config('local_fastpix', 'webhook_secret_rotated_at');
    if ($previous !== '' && ($rotatedat > 0) && (time() - $rotatedat) < self::ROTATION_WINDOW) {
        if (strlen($previous) < self::MIN_SECRET_BYTES) {
            $this->log_short_secret('previous', strlen($previous));
            return false;
        }
        if ($this->matches_either_format($rawbody, $previous, $signatureheader)) {
            return true;
        }
    }

    return false;
}

    /**
     * Compare the provided signature against the canonical FastPix shape:
     *   keyBytes = base64_decode(SECRET)
     *   sig      = base64_encode(hmac_sha256(keyBytes, body))
     * Empirically verified 2026-05-07 against the FastPix sandbox; matches
     * the Express reference verifier in FastPix's docs. Three legacy
     * fallbacks (raw-string secret, hex output, mixed) live behind
     * LOCAL_FASTPIX_DEBUG_VERIFIER so the test suite can drive synthetic
     * fixtures without enlarging the production attack surface.
     * Per rule S3, all compares use hash_equals.
     *
     * @param string $rawbody
     * @param string $secret
     * @param string $signatureheader
     * @return bool
     */
private function matches_either_format(string $rawbody, string $secret, string $signatureheader): bool {
    // FastPix canonical: secret is base64; output is base64.
    $decodedsecret = base64_decode($secret, true);
    if ($decodedsecret !== false && $decodedsecret !== '') {
        $rawhmac = hash_hmac(self::HMAC_ALGO, $rawbody, $decodedsecret, true);
        if ($this->constant_time_compare(base64_encode($rawhmac), $signatureheader)) {
            return true;
        }
    }

    // Test-only fallbacks. Gated by a constant the production bootstrap.
    // Never defines; tests opt in by defining it before driving verify().
    if (defined('LOCAL_FASTPIX_DEBUG_VERIFIER') && LOCAL_FASTPIX_DEBUG_VERIFIER) {
        if ($decodedsecret !== false && $decodedsecret !== '') {
            $rawhmac = hash_hmac(self::HMAC_ALGO, $rawbody, $decodedsecret, true);
            if ($this->constant_time_compare(bin2hex($rawhmac), $signatureheader)) {
                return true;
            }
        }
        $rawhmacstr = hash_hmac(self::HMAC_ALGO, $rawbody, $secret, true);
        if ($this->constant_time_compare(base64_encode($rawhmacstr), $signatureheader)) {
            return true;
        }
        if ($this->constant_time_compare(bin2hex($rawhmacstr), $signatureheader)) {
            return true;
        }
    }

    return false;
}

    /**
     * Single structured log line on the short-secret rejection path.
     * Same JSON shape as gateway.call lines so ops can grep one log
     * stream. The secret value is NEVER included — only its length.
     *
     * @param string $slot
     * @param int $length
     */
private function log_short_secret(string $slot, int $length): void {
    // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
    error_log(json_encode([
        'event'  => 'webhook.secret_too_short',
        'slot'   => $slot,
        'length' => $length,
        'min'    => self::MIN_SECRET_BYTES,
    ]));
}

    /**
     * Constant-time signature comparison. Wrapping hash_equals here makes
     * the static-analysis grep for forbidden comparisons (rule S3) trivial.
     *
     * @param string $expected
     * @param string $provided
     * @return bool
     */
private function constant_time_compare(string $expected, string $provided): bool {
    return hash_equals($expected, $provided);
}
}
