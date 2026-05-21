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
 * Tests for the upload service.
 *
 * @covers \local_fastpix\service\upload_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upload_service_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_upload_session';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        upload_service::reset();
        feature_flag_service::reset();
        \local_fastpix\api\gateway::reset();
        \cache::make('local_fastpix', 'upload_dedup')->purge();
    }

    public function tearDown(): void {
        parent::tearDown();
        upload_service::reset();
        feature_flag_service::reset();
        \local_fastpix\api\gateway::reset();
    }

    /**
     * Helper: inject gateway mock.
     *
     * @param mixed $mock
     */
    private function inject_gateway_mock($mock): void {
        $reflection = new \ReflectionClass(\local_fastpix\api\gateway::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $mock);
    }

    /**
     * Helper: default file upload response.
     *
     * @param string $uploadid
     * @return \stdClass
     */
    private function default_file_upload_response(string $uploadid = 'u1'): \stdClass {
        return (object)['data' => (object)[
            'uploadId' => $uploadid,
            'url'      => 'https://up.fastpix.io/' . $uploadid,
        ]];
    }

    /**
     * Helper: default url pull response.
     *
     * @param string $mediaid
     * @return \stdClass
     */
    private function default_url_pull_response(string $mediaid = 'media-pull-1'): \stdClass {
        return (object)['data' => (object)[
            'id'     => $mediaid,
            'status' => 'preparing',
        ]];
    }

    // A. File upload happy path.

    /**
     * Test that create file upload session inserts session and returns response.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_inserts_session_and_returns_response(): void {
        global $DB;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturn($this->default_file_upload_response('u-happy'));
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100]
        );

        $this->assertSame('u-happy', $resp->upload_id);
        $this->assertFalse($resp->deduped);
        $this->assertTrue($DB->record_exists(self::TABLE, ['upload_id' => 'u-happy']));
    }

    /**
     * Test that create file upload session persists owner hash metadata.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_persists_owner_hash_metadata(): void {
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        $expectedhash = hash_hmac('sha256', '42', 'fixed-salt-for-test');

        $captured = null;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturnCallback(function ($ownerhash, $metadata, $accesspolicy, $drmconfigid) use (&$captured) {
                $captured = [
                'owner_hash'    => $ownerhash,
                'metadata'      => $metadata,
                'access_policy' => $accesspolicy,
                'drm_config_id' => $drmconfigid,
                ];
                return (object)['data' => (object)['uploadId' => 'u', 'url' => 'https://x']];
            });
        $this->inject_gateway_mock($mock);

        upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'b.mp4', 'size' => 200]
        );

        $this->assertSame($expectedhash, $captured['owner_hash']);
        $this->assertSame($expectedhash, $captured['metadata']['moodle_owner_userhash']);
        $this->assertArrayHasKey('moodle_site_url', $captured['metadata']);
        $this->assertStringNotContainsString('42', $captured['metadata']['moodle_owner_userhash']);
    }

    /**
     * Test that create file upload session throws coding exception when user hash salt empty.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_throws_coding_exception_when_user_hash_salt_empty(): void {
        // Per T1.5 (REVIEW §4): the in-request salt-bootstrap fallback was.
        // A race anti-pattern (concurrent first-uses produced different salts).
        // Db/install.php now bootstraps the salt at install time; an empty.
        // Salt at runtime is genuinely abnormal and must surface, not.
        // Silently regenerate. Replaces the legacy auto-gen test.
        set_config('user_hash_salt', '', 'local_fastpix');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        // Gateway must not be called — owner_hash throws before we get there.
        $mock->expects($this->never())->method('input_video_direct_upload');
        $this->inject_gateway_mock($mock);

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessageMatches('/user_hash_salt config is empty/');
        upload_service::instance()->create_file_upload_session(
            7,
            ['filename' => 'c.mp4', 'size' => 50]
        );
    }

    // B. Deduplication.

    /**
     * Test that create file upload session within 60s returns deduped true.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_within_60s_returns_deduped_true(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->once())
            ->method('input_video_direct_upload')
            ->willReturn($this->default_file_upload_response('u-dedup'));
        $this->inject_gateway_mock($mock);

        $first = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100]
        );
        $second = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100]
        );

        $this->assertFalse($first->deduped);
        $this->assertTrue($second->deduped);
        $this->assertSame($first->session_id, $second->session_id);
    }

    /**
     * Test that create file upload session after 60s creates new session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_after_60s_creates_new_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->exactly(2))
            ->method('input_video_direct_upload')
            ->willReturnOnConsecutiveCalls(
                $this->default_file_upload_response('u-1st'),
                $this->default_file_upload_response('u-2nd'),
            );
        $this->inject_gateway_mock($mock);

        $first = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100]
        );

        // Simulate 60s TTL expiry by purging the dedup cache.
        \cache::make('local_fastpix', 'upload_dedup')->purge();

        $second = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100]
        );

        $this->assertFalse($second->deduped);
        $this->assertNotSame($first->session_id, $second->session_id);
    }

    /**
     * Test that create file upload session different user creates new session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_different_user_creates_new_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->exactly(2))
            ->method('input_video_direct_upload')
            ->willReturnOnConsecutiveCalls(
                $this->default_file_upload_response('u-userA'),
                $this->default_file_upload_response('u-userB'),
            );
        $this->inject_gateway_mock($mock);

        $a = upload_service::instance()->create_file_upload_session(
            1,
            ['filename' => 'a.mp4', 'size' => 100]
        );
        $b = upload_service::instance()->create_file_upload_session(
            2,
            ['filename' => 'a.mp4', 'size' => 100]
        );

        $this->assertFalse($a->deduped);
        $this->assertFalse($b->deduped);
        $this->assertNotSame($a->session_id, $b->session_id);
    }

    /**
     * Test that create file upload session different filename creates new session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_different_filename_creates_new_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->exactly(2))
            ->method('input_video_direct_upload')
            ->willReturnOnConsecutiveCalls(
                $this->default_file_upload_response('u-fileA'),
                $this->default_file_upload_response('u-fileB'),
            );
        $this->inject_gateway_mock($mock);

        $a = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100]
        );
        $b = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'b.mp4', 'size' => 100]
        );

        $this->assertNotSame($a->session_id, $b->session_id);
    }

    // C. DRM gate.

    /**
     * Test that create file upload session drm required with drm disabled throws.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_drm_required_with_drm_disabled_throws(): void {
        set_config('feature_drm_enabled', 0, 'local_fastpix');
        set_config('drm_configuration_id', '', 'local_fastpix');

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('input_video_direct_upload');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\drm_not_configured::class);
        upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100],
            drmrequired: true,
        );
    }

    /**
     * Test that create file upload session drm required with drm enabled passes.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_drm_required_with_drm_enabled_passes(): void {
        set_config('feature_drm_enabled', 1, 'local_fastpix');
        set_config('drm_configuration_id', 'cfg-1', 'local_fastpix');

        $captured = null;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturnCallback(function ($oh, $md, $accesspolicy, $drmconfigid) use (&$captured) {
                $captured = [
                'access_policy' => $accesspolicy,
                'drm_config_id' => $drmconfigid,
                ];
                return (object)['data' => (object)['uploadId' => 'u-drm', 'url' => 'https://x']];
            });
        $this->inject_gateway_mock($mock);

        upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100],
            drmrequired: true,
        );

        $this->assertSame('drm', $captured['access_policy']);
        $this->assertSame('cfg-1', $captured['drm_config_id']);
    }

    /**
     * Edge case: caller passes accesspolicy='drm' WITHOUT drmrequired=true.
     * The effective policy is still 'drm', so the W12 double-gate must fire
     * when DRM is not configured. Regression test for the gate previously
     * keying off the raw $drmrequired flag instead of the effective policy.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_drm_via_caller_policy_with_drm_disabled_throws(): void {
        set_config('feature_drm_enabled', 0, 'local_fastpix');
        set_config('drm_configuration_id', '', 'local_fastpix');

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('input_video_direct_upload');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\drm_not_configured::class);
        upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100],
            drmrequired: false,
            accesspolicy: 'drm',
        );
    }

    /**
     * Edge case: admin default_access_policy='drm' while DRM is not configured.
     * Every upload then resolves to 'drm' with drmrequired=false; the gate must
     * still reject rather than send accessPolicy=drm with no drmConfigurationId.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_file_upload_session_drm_via_default_config_with_drm_disabled_throws(): void {
        set_config('feature_drm_enabled', 0, 'local_fastpix');
        set_config('drm_configuration_id', '', 'local_fastpix');
        set_config('default_access_policy', 'drm', 'local_fastpix');

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('input_video_direct_upload');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\drm_not_configured::class);
        upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'a.mp4', 'size' => 100],
        );
    }

    // D. URL pull SSRF.

    /**
     * Test that create url pull session https public ip succeeds.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_https_public_ip_succeeds(): void {
        // 1.2.3.4 Is a public IP literal; gethostbynamel returns it unchanged.
        // And FILTER_VALIDATE_IP without restrictive flags accepts it.
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('media_create_from_url')->willReturn($this->default_url_pull_response('m-pull-ok'));
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/video.mp4'
        );
        $this->assertSame('m-pull-ok', $resp->upload_id);
    }

    /**
     * Test that create url pull session rejects http scheme.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_http_scheme(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'http://example.com/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('non_https', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects credentials in url.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_credentials_in_url(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(
                42,
                'https://user:pass@example.com/v.mp4'
            );
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('credentials_in_url', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects localhost.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_localhost(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://localhost/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('local_host:localhost', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects dot local domain.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_dot_local_domain(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\ssrf_blocked::class);
        upload_service::instance()->create_url_pull_session(42, 'https://myserver.local/v.mp4');
    }

    /**
     * Test that create url pull session rejects rfc1918 ip directly.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_rfc1918_ip_directly(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://10.0.0.1/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ip:10.0.0.1', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects loopback ip directly.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_loopback_ip_directly(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://127.0.0.1/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ip:127.0.0.1', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects link local aws metadata.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_link_local_aws_metadata(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://169.254.169.254/latest/meta-data');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ip:169.254.169.254', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects unresolvable host.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_unresolvable_host(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(
                42,
                'https://this-domain-does-not-exist-xyz123.example/v.mp4'
            );
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('unresolvable', (string)$e->a);
        }
    }

    // E. Persistence.

    // IPv6 SSRF guard tests (T1.3, REVIEW S-2).
    // The existing IPv4 tests above passed with the old guard, but the old.
    // Guard used gethostbynamel() which returns A records only — AAAA records.
    // Were silently ignored. These IPv6 tests would have all been bypassed.
    // Before T1.3.

    /**
     * Test that create url pull session rejects ipv6 loopback.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv6_loopback(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[::1]/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ipv6:', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects ipv6 ula fd00.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv6_ula_fd00(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[fd00::1]/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ipv6:', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects ipv6 ula fc00.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv6_ula_fc00(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[fc00::1]/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ipv6:', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects ipv6 link local.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv6_link_local(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[fe80::1]/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ipv6:', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects ipv6 aws metadata.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv6_aws_metadata(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[fd00:ec2::254]/latest/meta-data');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ipv6:', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects ipv6 nat64.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv6_nat64(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[64:ff9b::a00:1]/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            $this->assertStringContainsString('blocked_ipv6:', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session rejects ipv4 mapped ipv6 with private v4.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_rejects_ipv4_mapped_ipv6_with_private_v4(): void {
        // The address ::ffff:192.168.1.1 is RFC4291 IPv4-mapped IPv6; the embedded v4.
        // Is RFC1918 private and must be re-validated as IPv4 (recursive.
        // Assert_ip_public call), then rejected with the IPv4 error tag.
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('media_create_from_url');
        $this->inject_gateway_mock($mock);

        try {
            upload_service::instance()->create_url_pull_session(42, 'https://[::ffff:192.168.1.1]/v.mp4');
            $this->fail('expected ssrf_blocked');
        } catch (\local_fastpix\exception\ssrf_blocked $e) {
            // Recursive call into the IPv4 path produces the IPv4 tag.
            $this->assertStringContainsString('blocked_ip:192.168.1.1', (string)$e->a);
        }
    }

    /**
     * Test that create url pull session allows public ipv6 literal.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_allows_public_ipv6_literal(): void {
        // Cloudflare's public DNS server. If this is rejected, the IPv6.
        // Private-range checks are too aggressive and would break URL pull.
        // From any IPv6-only public CDN.
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->once())
            ->method('media_create_from_url')
            ->willReturn($this->default_url_pull_response());
        $this->inject_gateway_mock($mock);

        // Should NOT throw ssrf_blocked.
        $result = upload_service::instance()->create_url_pull_session(
            42,
            'https://[2606:4700:4700::1111]/v.mp4'
        );
        $this->assertNotNull($result);
    }

    /**
     * Test that url pull session stores source url not upload url.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_url_pull_session_stores_source_url_not_upload_url(): void {
        global $DB;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('media_create_from_url')
            ->willReturn($this->default_url_pull_response('m-store-1'));
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/source.mp4'
        );

        $stored = $DB->get_record(self::TABLE, ['id' => $resp->session_id]);
        $this->assertSame('https://1.2.3.4/source.mp4', $stored->source_url);
        $this->assertSame('', (string)$stored->upload_url);
    }

    // M5: URL-pull dedup window.

    /**
     * Test that create url pull session within 60s returns deduped true.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_within_60s_returns_deduped_true(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->once())
            ->method('media_create_from_url')
            ->willReturn($this->default_url_pull_response('m-urlpull-dedup'));
        $this->inject_gateway_mock($mock);

        $first = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/sample.mp4'
        );
        $second = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/sample.mp4'
        );

        $this->assertFalse($first->deduped);
        $this->assertTrue($second->deduped);
        $this->assertSame($first->session_id, $second->session_id);
    }

    /**
     * Test that create url pull session after 60s creates new session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_after_60s_creates_new_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->exactly(2))
            ->method('media_create_from_url')
            ->willReturnOnConsecutiveCalls(
                $this->default_url_pull_response('m-urlpull-1'),
                $this->default_url_pull_response('m-urlpull-2'),
            );
        $this->inject_gateway_mock($mock);

        $first = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/sample.mp4'
        );

        \cache::make('local_fastpix', 'upload_dedup')->purge();

        $second = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/sample.mp4'
        );

        $this->assertFalse($second->deduped);
        $this->assertNotSame($first->session_id, $second->session_id);
    }

    /**
     * Test that get status returns dto for owners session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_get_status_returns_dto_for_owners_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturn($this->default_file_upload_response('u-status'));
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'status.mp4', 'size' => 100]
        );
        $status = upload_service::instance()->get_status($resp->session_id, 42);
        $this->assertSame($resp->session_id, $status->session_id);
        $this->assertSame('u-status', $status->upload_id);
        $this->assertSame('pending', $status->state);
    }

    /**
     * Test that get status throws asset not found for other users session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_get_status_throws_asset_not_found_for_other_users_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturn($this->default_file_upload_response('u-private'));
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_file_upload_session(
            1,
            ['filename' => 'a.mp4', 'size' => 100]
        );

        $this->expectException(\local_fastpix\exception\asset_not_found::class);
        upload_service::instance()->get_status($resp->session_id, 9999);
    }

    /**
     * Test that get status throws asset not found for unknown session id.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_get_status_throws_asset_not_found_for_unknown_session_id(): void {
        $this->expectException(\local_fastpix\exception\asset_not_found::class);
        upload_service::instance()->get_status(999999, 1);
    }

    /**
     * Test that create url pull session different source url creates new session.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_url_pull_session_different_source_url_creates_new_session(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->exactly(2))
            ->method('media_create_from_url')
            ->willReturnOnConsecutiveCalls(
                $this->default_url_pull_response('m-urlA'),
                $this->default_url_pull_response('m-urlB'),
            );
        $this->inject_gateway_mock($mock);

        $a = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/a.mp4'
        );
        $b = upload_service::instance()->create_url_pull_session(
            42,
            'https://1.2.3.4/b.mp4'
        );

        $this->assertFalse($a->deduped);
        $this->assertFalse($b->deduped);
        $this->assertNotSame($a->session_id, $b->session_id);
    }

    // Z. Large-file regression — bytes never transit PHP (rule A1/A2).

    /**
     * Regression for large-upload crash report (2026-05). A 5 GB upload
     * request must:
     *   - reach the gateway with metadata only (no file body),
     *   - return a signed upload_url for the browser to PUT directly,
     *   - allocate trivial peak memory in this PHP process (< 4 MB delta).
     * If anyone ever re-wires this path to proxy bytes through PHP, the
     * memory assertion below trips first.
     *
     * @covers \local_fastpix\service\upload_service::create_file_upload_session
     */
    public function test_create_file_upload_session_does_not_buffer_bytes_for_large_file(): void {
        $fivegb = 5 * 1024 * 1024 * 1024; // Five gibibytes.

        $capturedargs = null;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->once())
            ->method('input_video_direct_upload')
            ->willReturnCallback(function (...$args) use (&$capturedargs) {
                $capturedargs = $args;
                return (object)['data' => (object)[
                    'uploadId' => 'u-large',
                    'url'      => 'https://up.fastpix.io/u-large',
                ]];
            });
        $this->inject_gateway_mock($mock);

        $before = memory_get_peak_usage(true);
        $resp = upload_service::instance()->create_file_upload_session(
            42,
            ['filename' => 'huge-lecture.mp4', 'size' => $fivegb]
        );
        $after = memory_get_peak_usage(true);

        // Service returned a signed URL for the browser, not a byte channel.
        $this->assertSame('u-large', $resp->upload_id);
        $this->assertSame('https://up.fastpix.io/u-large', $resp->upload_url);
        $this->assertFalse($resp->deduped);

        // Gateway received metadata only — no 'file', 'body', or 'bytes'
        // arg of any kind. The fixed arity here is part of the A1/A2
        // contract; if a sixth byte-carrying arg is ever introduced this
        // assertion fails and routes back to @upload-service.
        $this->assertCount(5, $capturedargs);
        foreach ($capturedargs as $i => $arg) {
            $this->assertNotInstanceOf(\SplFileObject::class, $arg, "arg $i is a file handle");
            if (is_string($arg)) {
                // Sanity: no multi-GB string snuck into a gateway arg.
                $this->assertLessThan(1024 * 1024, strlen($arg), "arg $i exceeds 1 MiB");
            }
        }

        // Peak memory delta must be negligible (well under a single chunk
        // of a 5 GB file). 4 MiB headroom for PHPUnit framework noise.
        $this->assertLessThan(
            4 * 1024 * 1024,
            $after - $before,
            'create_file_upload_session allocated >4MiB for a 5GB upload — bytes are being buffered'
        );
    }
}
