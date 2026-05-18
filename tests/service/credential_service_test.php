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

/**
 * Tests for the credential service.
 *
 * @covers \local_fastpix\service\credential_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class credential_service_test extends \advanced_testcase {
    /** @var string */
    private const FAKE_PEM =
        "-----BEGIN PRIVATE KEY-----\nFAKEPEMCONTENT\n-----END PRIVATE KEY-----";

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        credential_service::reset();
    }

    public function tearDown(): void {
        parent::tearDown();
        credential_service::reset();
    }

    /**
     * Helper: gateway mock returning signing key.
     *
     * @return \local_fastpix\api\gateway
     */
    private function gateway_mock_returning_signing_key(): \local_fastpix\api\gateway {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('create_signing_key')->willReturn((object)[
            'id'         => 'kid-test-1',
            'privateKey' => self::FAKE_PEM,
            'createdAt'  => '2026-05-04T00:00:00Z',
        ]);
        return $mock;
    }

    // Apikey / apisecret.

    /**
     * Test that apikey returns configured value.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_apikey_returns_configured_value(): void {
        set_config('apikey', 'sk-test-123', 'local_fastpix');
        $this->assertSame('sk-test-123', credential_service::instance()->apikey());
    }

    /**
     * Test that apikey throws credentials missing when empty.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_apikey_throws_credentials_missing_when_empty(): void {
        try {
            credential_service::instance()->apikey();
            $this->fail('expected moodle_exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('credentials_missing', $e->errorcode);
        }
    }

    /**
     * Test that apisecret returns configured value.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_apisecret_returns_configured_value(): void {
        set_config('apisecret', 'shh-very-secret', 'local_fastpix');
        $this->assertSame('shh-very-secret', credential_service::instance()->apisecret());
    }

    /**
     * Test that apisecret throws credentials missing when empty.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_apisecret_throws_credentials_missing_when_empty(): void {
        try {
            credential_service::instance()->apisecret();
            $this->fail('expected moodle_exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('credentials_missing', $e->errorcode);
        }
    }

    // Ensure_signing_key.

    /**
     * Test that ensure signing key is idempotent when already configured.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_ensure_signing_key_is_idempotent_when_already_configured(): void {
        set_config('signing_key_id', 'pre-existing-kid', 'local_fastpix');
        set_config('signing_private_key', base64_encode(self::FAKE_PEM), 'local_fastpix');

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('create_signing_key');

        $service = credential_service::instance();
        $service->set_gateway($mock);
        $service->ensure_signing_key();

        // Config left untouched.
        $this->assertSame('pre-existing-kid', get_config('local_fastpix', 'signing_key_id'));
    }

    /**
     * Test that ensure signing key calls gateway when not configured.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_ensure_signing_key_calls_gateway_when_not_configured(): void {
        set_config('signing_key_id', '', 'local_fastpix');
        set_config('signing_private_key', '', 'local_fastpix');

        $service = credential_service::instance();
        $service->set_gateway($this->gateway_mock_returning_signing_key());
        $service->ensure_signing_key();

        $this->assertSame('kid-test-1', get_config('local_fastpix', 'signing_key_id'));
        $this->assertNotEmpty(get_config('local_fastpix', 'signing_private_key'));
    }

    /**
     * Test that ensure signing key stores pem base64 encoded.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_ensure_signing_key_stores_pem_base64_encoded(): void {
        set_config('signing_key_id', '', 'local_fastpix');
        set_config('signing_private_key', '', 'local_fastpix');

        $service = credential_service::instance();
        $service->set_gateway($this->gateway_mock_returning_signing_key());
        $service->ensure_signing_key();

        $stored = (string)get_config('local_fastpix', 'signing_private_key');
        $decoded = base64_decode($stored, true);
        $this->assertSame(self::FAKE_PEM, $decoded);
    }

    // Redaction canary.

    /**
     * Test that redaction canary no pem in logs.
     *
     * @covers \local_fastpix\service\credential_service
     */
    public function test_redaction_canary_no_pem_in_logs(): void {
        set_config('signing_key_id', '', 'local_fastpix');
        set_config('signing_private_key', '', 'local_fastpix');

        $tmp = tempnam(sys_get_temp_dir(), 'credlog_');
        $original = ini_get('error_log');
        ini_set('error_log', $tmp);

        try {
            $service = credential_service::instance();
            $service->set_gateway($this->gateway_mock_returning_signing_key());
            $service->ensure_signing_key();
            $logbuffer = (string)file_get_contents($tmp);
        } finally {
            ini_set('error_log', $original);
            @unlink($tmp);
        }

        $this->assertStringNotContainsString('FAKEPEMCONTENT', $logbuffer);
        $this->assertStringNotContainsString('-----BEGIN', $logbuffer);
        $this->assertStringNotContainsString(self::FAKE_PEM, $logbuffer);

        // The kid is fine to log — it is not a secret.
        $this->assertStringContainsString('kid-test-1', $logbuffer);
    }
}
