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
 * Service: jwt signing service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

use Firebase\JWT\JWT;
use local_fastpix\exception\signing_key_missing;

/**
 * Service: jwt signing.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jwt_signing_service {
    /** @var int Token ttl seconds. */
    private const TOKEN_TTL_SECONDS = 300;
    /** @var string JWT issuer for manifest playback tokens. Matches FastPix's own generator. */
    private const ISS = 'fastpix.com';
    /** @var string JWT issuer for DRM license tokens. Same as the manifest issuer. */
    private const ISS_DRM = 'fastpix.com';

    /**
     * Sign for playback.
     *
     * @param string $playbackid
     * @param ?int $ttl
     * @return string
     */
    public function sign_for_playback(string $playbackid, ?int $ttl = null): string {
        $kid = (string)get_config('local_fastpix', 'signing_key_id');
        $privatekeyb64 = (string)get_config('local_fastpix', 'signing_private_key');

        if ($kid === '' || $privatekeyb64 === '') {
            throw new signing_key_missing('config_empty');
        }

        $pem = base64_decode($privatekeyb64, true);
        if ($pem === false) {
            throw new signing_key_missing('invalid_base64');
        }

        $now = time();
        // FastPix playback JWT format, verified against the secured-playback
        // docs: kid in both payload and header, aud is "media:<playback_id>",
        // sub is empty (reserved), and iss is fastpix.com (the ISS constant).
        $payload = [
            'kid' => $kid,
            'aud' => 'media:' . $playbackid,
            'iss' => self::ISS,
            'sub' => '',
            'iat' => $now,
            'exp' => $now + ($ttl ?? self::TOKEN_TTL_SECONDS),
        ];

        return JWT::encode($payload, $pem, 'RS256', $kid);
    }

    /**
     * Sign for DRM license server (Widevine / FairPlay / PlayReady).
     *
     * Verified against a real DRM token captured from FastPix's own
     * generator. The DRM token differs from the manifest token in one
     * way: aud = "drm:<playback_id>" instead of "media:<playback_id>".
     * Issuer (fastpix.com), signing key, and private key are identical
     * to the manifest token.
     *
     * @param string $playbackid The asset's FastPix playback id.
     * @param ?int $ttl
     * @return string
     */
    public function sign_for_drm(string $playbackid, ?int $ttl = null): string {
        $kid = (string)get_config('local_fastpix', 'signing_key_id');
        $privatekeyb64 = (string)get_config('local_fastpix', 'signing_private_key');

        if ($kid === '' || $privatekeyb64 === '') {
            throw new signing_key_missing('config_empty');
        }

        $pem = base64_decode($privatekeyb64, true);
        if ($pem === false) {
            throw new signing_key_missing('invalid_base64');
        }

        $now = time();
        $payload = [
            'kid' => $kid,
            'aud' => 'drm:' . $playbackid,
            'iss' => self::ISS_DRM,
            'sub' => '',
            'iat' => $now,
            'exp' => $now + ($ttl ?? self::TOKEN_TTL_SECONDS),
        ];

        return JWT::encode($payload, $pem, 'RS256', $kid);
    }

    /**
     * Token ttl seconds.
     *
     * @return int
     */
    public function token_ttl_seconds(): int {
        return self::TOKEN_TTL_SECONDS;
    }
}
