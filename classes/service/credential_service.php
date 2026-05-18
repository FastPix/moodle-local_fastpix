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
 * Service: credential service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

/**
 * Service: credential.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class credential_service {
    /** @var ?self $instance */
    private static ?self $instance = null;

    /** @var ?\local_fastpix\api\gateway $gateway */
    private ?\local_fastpix\api\gateway $gateway = null;

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
     * DI seam for tests — inject a mocked gateway. Production code uses
     * \local_fastpix\api\gateway::instance() lazily.
     *
     * @param \local_fastpix\api\gateway $gateway
     */
public function set_gateway(\local_fastpix\api\gateway $gateway): void {
    $this->gateway = $gateway;
}

    /**
     * Apikey.
     *
     * @return string
     */
public function apikey(): string {
    $value = (string)get_config('local_fastpix', 'apikey');
    if ($value === '') {
        throw new \moodle_exception(
            'credentials_missing',
            'local_fastpix',
            '',
            'apikey or apisecret not configured'
        );
    }
    return $value;
}

    /**
     * Apisecret.
     *
     * @return string
     */
public function apisecret(): string {
    $value = (string)get_config('local_fastpix', 'apisecret');
    if ($value === '') {
        throw new \moodle_exception(
            'credentials_missing',
            'local_fastpix',
            '',
            'apikey or apisecret not configured'
        );
    }
    return $value;
}

    /**
     * Bootstrap the local RS256 signing key on first call. Idempotent.
     * Stores the kid and a base64-encoded PEM in mdl_config_plugins.
     * NEVER logs the private key.
     */
public function ensure_signing_key(): void {
    // Fast path: already minted, no lock needed.
    $kid = (string)get_config('local_fastpix', 'signing_key_id');
    $pem = (string)get_config('local_fastpix', 'signing_private_key');
    if ($kid !== '' && $pem !== '') {
        return;
    }

    // Concurrency: under PHP-FPM, two workers can both pass the check.
    // Above and both call create_signing_key — leaking one key on the.
    // FastPix side. Use \core\lock to serialize first-time bootstrap.
    // Per REVIEW-2026-05-04 §4 (concurrency).
    $factory = \core\lock\lock_config::get_lock_factory('local_fastpix_signing_key');
    $lock = $factory->get_lock('ensure', 30);
    if (!$lock) {
        throw new \local_fastpix\exception\lock_acquisition_failed(
            'ensure_signing_key'
        );
    }

    try {
        // Double-check inside the lock: another worker may have just.
        // Bootstrapped while we were waiting. If so, nothing to do.
        $kid = (string)get_config('local_fastpix', 'signing_key_id');
        $pem = (string)get_config('local_fastpix', 'signing_private_key');
        if ($kid !== '' && $pem !== '') {
            return;
        }

        $response = ($this->gateway ?? \local_fastpix\api\gateway::instance())->create_signing_key();

        // FastPix wraps the response: {"success": true, "data": {"id": ..., "privateKey": ...}}.
        // Unit-test mocks sometimes return the unwrapped shape; accept both.
        $payload = $response->data ?? $response;
        $newkid = (string)($payload->id ?? '');
        $newpemfield = (string)($payload->privateKey ?? '');

        if ($newkid === '' || $newpemfield === '') {
            throw new \local_fastpix\exception\signing_key_missing(
                'gateway returned empty kid or privateKey field'
            );
        }

        // FastPix returns privateKey ALREADY base64-encoded. Some unit-test.
        // Mocks return a raw PEM string. Normalize so what we store is.
        // Exactly one base64 layer over a real PEM — which jwt_signing_service.
        // Can decode and feed straight into openssl_pkey_get_private().
        $decodedonce = base64_decode($newpemfield, true);
        $lookslikepem = $decodedonce !== false
            && str_contains($decodedonce, '-----BEGIN');
        $newpemb64 = $lookslikepem
            ? $newpemfield                      // Already base64'd PEM — store as-is.
            : base64_encode($newpemfield);      // Raw PEM (test mock) — encode once.

        set_config('signing_key_id', $newkid, 'local_fastpix');
        set_config('signing_private_key', $newpemb64, 'local_fastpix');
        set_config('signing_key_created_at', time(), 'local_fastpix');

        // Log only the kid; the private key never appears in any log line (S2).
        // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
        error_log(json_encode([
            'event' => 'credential.signing_key_bootstrapped',
            'id'    => $newkid,
        ]));
    } finally {
        // Release MUST run even if create_signing_key threw, so the.
        // Next worker can retry instead of waiting 30s for stale lock.
        $lock->release();
    }
}
}
