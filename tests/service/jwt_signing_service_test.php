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

namespace local_fastpix\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Tests for the JWT signing service.
 *
 * @covers \local_fastpix\service\jwt_signing_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class jwt_signing_service_test extends \advanced_testcase {
    /** @var mixed */
    private string $privatepem = '';
    /** @var mixed */
    private string $publicpem = '';
    /** @var string */
    private const KID = 'test-kid';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($resource, 'failed to generate test RSA key');

        openssl_pkey_export($resource, $privatepem);
        $details = openssl_pkey_get_details($resource);

        $this->privatepem = $privatepem;
        $this->publicpem = $details['key'];

        set_config('signing_key_id', self::KID, 'local_fastpix');
        set_config('signing_private_key', base64_encode($this->privatepem), 'local_fastpix');
    }

    /**
     * Helper: decode segments.
     *
     * @param string $jwt
     * @return array
     */
    private function decode_segments(string $jwt): array {
        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts, 'JWT must have three segments');
        $b64urldecode = static fn(string $s): string =>
            base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
        $header = json_decode($b64urldecode($parts[0]), true);
        $payload = json_decode($b64urldecode($parts[1]), true);
        return [$header, $payload, $parts];
    }

    // Config-missing branches.

    /**
     * Test that sign for playback with missing kid throws signing key missing.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_with_missing_kid_throws_signing_key_missing(): void {
        set_config('signing_key_id', '', 'local_fastpix');
        set_config('signing_private_key', 'abc', 'local_fastpix');

        try {
            (new jwt_signing_service())->sign_for_playback('pb-1');
            $this->fail('expected signing_key_missing');
        } catch (\local_fastpix\exception\signing_key_missing $e) {
            $this->assertStringContainsString('config_empty', $e->getMessage() . ' ' . (string)$e->a);
        }
    }

    /**
     * Test that sign for playback with missing pem throws signing key missing.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_with_missing_pem_throws_signing_key_missing(): void {
        set_config('signing_key_id', 'kid-1', 'local_fastpix');
        set_config('signing_private_key', '', 'local_fastpix');

        try {
            (new jwt_signing_service())->sign_for_playback('pb-1');
            $this->fail('expected signing_key_missing');
        } catch (\local_fastpix\exception\signing_key_missing $e) {
            $this->assertStringContainsString('config_empty', $e->getMessage() . ' ' . (string)$e->a);
        }
    }

    /**
     * Test that sign for playback with invalid base64 throws signing key missing.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_with_invalid_base64_throws_signing_key_missing(): void {
        set_config('signing_key_id', 'kid-1', 'local_fastpix');
        set_config('signing_private_key', '@@@invalid', 'local_fastpix');

        try {
            (new jwt_signing_service())->sign_for_playback('pb-1');
            $this->fail('expected signing_key_missing');
        } catch (\local_fastpix\exception\signing_key_missing $e) {
            $this->assertStringContainsString('invalid_base64', $e->getMessage() . ' ' . (string)$e->a);
        }
    }

    // Roundtrip / shape.

    /**
     * Test that sign for playback returns valid three segment jwt.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_returns_valid_three_segment_jwt(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-1');
        $this->assertCount(3, explode('.', $jwt));
    }

    /**
     * Test that sign for playback payload has correct aud format.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_payload_has_correct_aud_format(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-xyz');
        [, $payload] = $this->decode_segments($jwt);
        $this->assertSame('media:pb-xyz', $payload['aud']);
    }

    /**
     * Test that sign for playback uses rs256 in header.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_uses_rs256_in_header(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-1');
        [$header] = $this->decode_segments($jwt);
        $this->assertSame('RS256', $header['alg']);
    }

    /**
     * Test that sign for playback kid in header matches kid in payload.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_kid_in_header_matches_kid_in_payload(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-1');
        [$header, $payload] = $this->decode_segments($jwt);
        $this->assertSame($header['kid'], $payload['kid']);
        $this->assertSame(self::KID, $header['kid']);
    }

    /**
     * Test that sign for playback default ttl is 300.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_default_ttl_is_300(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-1');
        [, $payload] = $this->decode_segments($jwt);
        $this->assertSame(300, (int)$payload['exp'] - (int)$payload['iat']);
    }

    /**
     * Test that sign for playback custom ttl is honored.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_custom_ttl_is_honored(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-1', 60);
        [, $payload] = $this->decode_segments($jwt);
        $this->assertSame(60, (int)$payload['exp'] - (int)$payload['iat']);
    }

    /**
     * Test that sign for playback signature verifies with public key.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_sign_for_playback_signature_verifies_with_public_key(): void {
        $jwt = (new jwt_signing_service())->sign_for_playback('pb-verify');

        // Canonical roundtrip via firebase/php-jwt with the public key.
        $decoded = JWT::decode($jwt, new Key($this->publicpem, 'RS256'));
        $this->assertSame('media:pb-verify', $decoded->aud);

        // Belt-and-braces openssl_verify on the raw segments.
        $parts = explode('.', $jwt);
        $signinginput = $parts[0] . '.' . $parts[1];
        $signature = base64_decode(strtr($parts[2], '-_', '+/')
        . str_repeat('=', (4 - strlen($parts[2]) % 4) % 4));
        $verified = openssl_verify($signinginput, $signature, $this->publicpem, OPENSSL_ALGO_SHA256);
        $this->assertSame(1, $verified);
    }

    // Constants.

    /**
     * Test that token ttl seconds returns 300.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_token_ttl_seconds_returns_300(): void {
        $this->assertSame(300, (new jwt_signing_service())->token_ttl_seconds());
    }

    // Redaction canary.

    /**
     * Test that redaction canary no pem or jwt in logs.
     *
     * @covers \local_fastpix\service\jwt_signing_service
     */
    public function test_redaction_canary_no_pem_or_jwt_in_logs(): void {
        $logbuffer = '';
        $originalerrorlog = ini_get('error_log');
        $tmp = tempnam(sys_get_temp_dir(), 'jwtlog_');
        ini_set('error_log', $tmp);

        try {
            (new jwt_signing_service())->sign_for_playback('pb-canary');
            $logbuffer = (string)file_get_contents($tmp);
        } finally {
            ini_set('error_log', $originalerrorlog);
            @unlink($tmp);
        }

        $this->assertDoesNotMatchRegularExpression('/eyJ[A-Za-z0-9_-]{10,}/', $logbuffer);
        $this->assertStringNotContainsString('-----BEGIN', $logbuffer);
        $this->assertStringNotContainsString($this->privatepem, $logbuffer);
    }
}
