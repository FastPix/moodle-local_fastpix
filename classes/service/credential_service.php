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

    /** @var string Config key: signing key id. */
    private const CFG_KEY_ID = 'signing_key_id';
    /** @var string Config key: signing private key (base64 PEM). */
    private const CFG_PRIVATE_KEY = 'signing_private_key';
    /** @var string Config key: rotation-required flag. */
    private const CFG_ROTATION_REQUIRED = 'signing_key_rotation_required';

    /**
     * Private constructor — instantiate via the instance() singleton accessor.
     */
    private function __construct() {
        // Intentionally empty: the gateway is injected lazily via set_gateway()/instance().
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
    return $this->require_credential('apikey');
}

    /**
     * Apisecret.
     *
     * @return string
     */
public function apisecret(): string {
    return $this->require_credential('apisecret');
}

    /**
     * Return a required credential config value, or throw if it is not set.
     *
     * @param string $key The config key ('apikey' or 'apisecret').
     * @return string
     * @throws \moodle_exception when the credential is not configured.
     */
private function require_credential(string $key): string {
    $value = (string)get_config('local_fastpix', $key);
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
    // Rotation flag is set by \local_fastpix\admin\setting_credential when an
    // admin changes apikey or apisecret. If set, we MUST re-mint against the
    // (now new) credentials — the existing key was registered to the previous
    // FastPix workspace and produces tokens the new workspace's CDN rejects
    // with HTTP 401. Forces the lock path below.
    $rotationrequired = (string)get_config('local_fastpix', self::CFG_ROTATION_REQUIRED) === '1';

    if (!$rotationrequired) {
        // Fast path: already minted, no lock needed.
        $kid = (string)get_config('local_fastpix', self::CFG_KEY_ID);
        $pem = (string)get_config('local_fastpix', self::CFG_PRIVATE_KEY);
        if ($kid !== '' && $pem !== '') {
            return;
        }
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
        // Bootstrapped while we were waiting. If so, nothing to do — UNLESS
        // a rotation is required (credential change), in which case we must
        // proceed to mint a fresh key against the new credentials.
        $rotationrequired = (string)get_config('local_fastpix', self::CFG_ROTATION_REQUIRED) === '1';
        if (!$rotationrequired) {
            $kid = (string)get_config('local_fastpix', self::CFG_KEY_ID);
            $pem = (string)get_config('local_fastpix', self::CFG_PRIVATE_KEY);
            if ($kid !== '' && $pem !== '') {
                return;
            }
        } else {
            // Clear stale key state so the new bootstrap stores fresh values.
            // The previous-key slots are also cleared — they belonged to the
            // old workspace and can't verify tokens against the new one.
            unset_config(self::CFG_KEY_ID, 'local_fastpix');
            unset_config(self::CFG_PRIVATE_KEY, 'local_fastpix');
            unset_config('signing_key_id_previous', 'local_fastpix');
            unset_config('signing_private_key_previous', 'local_fastpix');
            unset_config('signing_key_created_at', 'local_fastpix');
            unset_config('signing_key_rotated_at', 'local_fastpix');
        }

        $this->mint_and_store_signing_key($rotationrequired);
    } finally {
        // Release MUST run even if create_signing_key threw, so the.
        // Next worker can retry instead of waiting 30s for stale lock.
        $lock->release();
    }
}

    /**
     * Mint a fresh RS256 signing key on the FastPix side and store it locally.
     * NEVER logs the private key (S2). The caller holds the bootstrap lock.
     *
     * @param bool $rotationtriggered Whether this mint was forced by a credential change.
     * @throws \local_fastpix\exception\signing_key_missing on an empty gateway payload.
     */
private function mint_and_store_signing_key(bool $rotationtriggered): void {
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

    // FastPix returns privateKey ALREADY base64-encoded. Some unit-test
    // mocks return a raw PEM string. Normalize so what we store is
    // exactly one base64 layer over a real PEM — which jwt_signing_service
    // can decode and feed straight into openssl_pkey_get_private().
    $decodedonce = base64_decode($newpemfield, true);
    $lookslikepem = $decodedonce !== false
        && str_contains($decodedonce, '-----BEGIN');
    $newpemb64 = $lookslikepem
        ? $newpemfield                      // Already base64'd PEM — store as-is.
        : base64_encode($newpemfield);      // Raw PEM (test mock) — encode once.

    set_config(self::CFG_KEY_ID, $newkid, 'local_fastpix');
    set_config(self::CFG_PRIVATE_KEY, $newpemb64, 'local_fastpix');
    set_config('signing_key_created_at', time(), 'local_fastpix');

    // Successful mint clears the rotation flag (idempotent if not set).
    unset_config(self::CFG_ROTATION_REQUIRED, 'local_fastpix');

    // Log only the kid; the private key never appears in any log line (S2).
    // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
    error_log(json_encode([
        'event' => 'credential.signing_key_bootstrapped',
        'id'    => $newkid,
        'rotation_triggered' => $rotationtriggered,
    ]));
}
}
