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
        // arg of any kind. The trailing arg is the (null here) subtitles bag,
        // not a byte channel; the loop below proves no arg carries bytes.
        $this->assertCount(7, $capturedargs);
        $this->assertNull($capturedargs[5], 'subtitles must be null for a no-captions file upload');
        $this->assertSame('', $capturedargs[6], 'title is empty on the legacy metadata-only file path');
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

    // Settings-based direct upload (title + access policy + captions).

    /**
     * Helper: insert a ready asset owned by the given user.
     *
     * @param string $fastpixid
     * @param int $owner
     * @return void
     */
    private function insert_ready_asset(string $fastpixid, int $owner): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_fastpix_asset', (object)[
            'fastpix_id'    => $fastpixid,
            'playback_id'   => null,
            'owner_userid'  => $owner,
            'title'         => 'T',
            'status'        => 'ready',
            'access_policy' => 'public',
            'drm_required'  => 0,
            'no_skip_required' => 0,
            'has_captions'  => 0,
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        \cache::make('local_fastpix', 'asset')->purge();
    }

    /**
     * Happy path: title + public policy + auto captions map onto the upload
     * and the session row is stamped with the chosen settings.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_with_settings_maps_title_policy_captions(): void {
        global $DB;
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        $captured = null;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturnCallback(function (...$args) use (&$captured) {
                $captured = $args;
                return (object)['data' => (object)[
                    'uploadId' => 'u-set-1',
                    'url'      => 'https://up.fastpix.com/u-set-1',
                ]];
            });
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_direct_upload_with_settings(
            7, 'My title', 'public', 'auto', 'en');

        $this->assertSame('https://up.fastpix.com/u-set-1', $resp->upload_url);
        $this->assertSame('u-set-1', $resp->upload_id);
        // Args: ownerhash, metadata, accesspolicy, drmconfigid, maxresolution, subtitles, title.
        // The title is FastPix's dedicated pushMediaSettings.title field (arg 6)
        // — NOT a metadata key — and it sets the media's data.title.
        $this->assertSame('My title', $captured[6]);
        $this->assertArrayNotHasKey('title', $captured[1]);
        $this->assertSame('public', $captured[2]);
        $this->assertNull($captured[3]);
        // subtitles is a single OBJECT (assoc array), never a list — a list
        // is rejected by FastPix with HTTP 400.
        $this->assertSame(['languageName' => 'English', 'languageCode' => 'en'], $captured[5]);

        $row = $DB->get_record(self::TABLE, ['upload_id' => 'u-set-1']);
        $this->assertSame('My title', $row->title);
        $this->assertSame('public', $row->access_policy);
        $this->assertSame('auto', $row->captions_mode);
        $this->assertSame('en', $row->language_code);
        $this->assertEquals(7, (int)$row->userid);
    }

    /**
     * The chosen courseid is persisted on the upload session (course-aware
     * uploads).
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_with_settings_persists_courseid(): void {
        global $DB;
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')->willReturn((object)['data' => (object)[
            'uploadId' => 'u-course-1', 'url' => 'https://up/x',
        ]]);
        $this->inject_gateway_mock($mock);

        upload_service::instance()->create_direct_upload_with_settings(7, 'T', 'public', 'none', null, 55);

        $this->assertEquals(55, (int)$DB->get_field(self::TABLE, 'courseid', ['upload_id' => 'u-course-1']));
    }

    /**
     * list_ready_for_course returns only the caller's ready, non-DRM,
     * non-deleted videos in the given course.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_list_ready_for_course_is_owner_course_ready_nondrm_scoped(): void {
        $want = $this->seed_asset(10, 7, 'ready', 'public');     // ← the only match.
        $this->seed_asset(10, 7, 'ready', 'drm');                 // DRM excluded.
        $this->seed_asset(10, 7, 'created', 'public');            // not ready excluded.
        $this->seed_asset(99, 7, 'ready', 'public');             // other course excluded.
        $this->seed_asset(10, 8, 'ready', 'public');             // other owner excluded.
        $this->seed_asset(10, 7, 'ready', 'public', true);       // soft-deleted excluded.

        $list = upload_service::instance()->list_ready_for_course(10, 7);

        $this->assertCount(1, $list);
        $this->assertSame($want, $list[0]->fastpix_id);
    }

    /**
     * Seed a linked asset + upload_session pair; returns the fastpix_id.
     *
     * @param int $courseid
     * @param int $owner
     * @param string $status
     * @param string $policy
     * @param bool $deleted
     * @return string
     */
    private function seed_asset(int $courseid, int $owner, string $status, string $policy, bool $deleted = false): string {
        global $DB;
        $now = time();
        $fpid = 'media-' . random_string(8);
        $DB->insert_record('local_fastpix_asset', (object)[
            'fastpix_id'    => $fpid,
            'playback_id'   => 'pb-' . random_string(6),
            'owner_userid'  => $owner,
            'title'         => 'T',
            'status'        => $status,
            'access_policy' => $policy,
            'drm_required'  => $policy === 'drm' ? 1 : 0,
            'no_skip_required' => 0,
            'has_captions'  => 0,
            'deleted_at'    => $deleted ? $now : null,
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        $DB->insert_record('local_fastpix_upload_session', (object)[
            'userid'      => $owner,
            'courseid'    => $courseid,
            'upload_id'   => 'upl-' . random_string(8),
            'upload_url'  => 'https://up/x',
            'fastpix_id'  => $fpid,
            'state'       => 'created',
            'timecreated' => $now,
            'expires_at'  => $now + 3600,
        ]);
        return $fpid;
    }

    /**
     * DRM intent sends FastPix accessPolicy 'drm' + drmConfigurationId.
     * (accessPolicy 'private' + a config id is rejected by FastPix with 400.)
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_with_settings_drm_sends_drm_policy_plus_config(): void {
        global $DB;
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        set_config('feature_drm_enabled', '1', 'local_fastpix');
        set_config('drm_configuration_id', 'drm-cfg-123', 'local_fastpix');
        $captured = null;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')
            ->willReturnCallback(function (...$args) use (&$captured) {
                $captured = $args;
                return (object)['data' => (object)['uploadId' => 'u-drm', 'url' => 'https://up/x']];
            });
        $this->inject_gateway_mock($mock);

        upload_service::instance()->create_direct_upload_with_settings(7, 'T', 'drm', 'none', null);

        $this->assertSame('drm', $captured[2]);
        $this->assertSame('drm-cfg-123', $captured[3]);
        $row = $DB->get_record(self::TABLE, ['upload_id' => 'u-drm']);
        $this->assertSame('drm', $row->access_policy);
    }

    /**
     * DRM requested but not configured fails hard — no gateway call, no
     * silent downgrade (W12).
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_with_settings_drm_without_config_fails(): void {
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('input_video_direct_upload');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\drm_not_configured::class);
        upload_service::instance()->create_direct_upload_with_settings(7, 'T', 'drm', 'none', null);
    }

    /**
     * An unsupported auto-caption language fails before any gateway call.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_create_with_settings_unsupported_language_fails(): void {
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('input_video_direct_upload');
        $this->inject_gateway_mock($mock);

        $this->expectException(\invalid_parameter_exception::class);
        upload_service::instance()->create_direct_upload_with_settings(7, 'T', 'public', 'auto', 'zz');
    }

    /**
     * End-to-end (ADR-015): a direct-upload session returns an integer
     * session_id, and the media.ready webhook links that session to the asset
     * and stamps the owner — so get_by_upload_session_id() resolves for
     * playback. This is the exact chain direct-upload playback depends on.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_direct_upload_session_links_to_asset_and_sets_owner(): void {
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')->willReturn((object)['data' => (object)[
            'uploadId' => 'm-link-1',
            'url'      => 'https://up.fastpix.com/m-link-1',
        ]]);
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->create_direct_upload_with_settings(
            7, 'T', 'public', 'none', null);
        $this->assertIsInt($resp->session_id);
        $this->assertGreaterThan(0, $resp->session_id);

        // media.ready for the upload's UUID links the session + stamps owner.
        \cache::make('local_fastpix', 'asset')->purge();
        $event = (object)[
            'id'         => 'evt-link',
            'type'       => 'video.media.ready',
            'occurredAt' => time(),
            'object'     => (object)['type' => 'media', 'id' => 'm-link-1'],
            'data'       => (object)['playbackIds' => [
                (object)['id' => 'pb-link', 'accessPolicy' => 'public'],
            ]],
        ];
        (new \local_fastpix\webhook\projector())->project($event);

        $asset = \local_fastpix\service\asset_service::get_by_upload_session_id((int)$resp->session_id);
        $this->assertNotNull($asset);
        $this->assertSame('m-link-1', $asset->fastpix_id);
        $this->assertEquals(7, (int)$asset->owner_userid);
    }

    /**
     * add_subtitle_track happy path for the owner.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_add_subtitle_track_owner_happy_path(): void {
        $this->insert_ready_asset('m-track-1', 7);
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('add_media_track')
            ->willReturn((object)['data' => (object)['id' => 'trk-1']]);
        $this->inject_gateway_mock($mock);

        $resp = upload_service::instance()->add_subtitle_track(
            7, 'm-track-1', 'en', 'https://1.2.3.4/s.vtt');
        $this->assertSame('trk-1', $resp->track_id);
    }

    /**
     * add_subtitle_track refuses a non-owner (looks like not-found).
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_add_subtitle_track_denies_non_owner(): void {
        $this->insert_ready_asset('m-track-2', 7);
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('add_media_track');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\asset_not_found::class);
        upload_service::instance()->add_subtitle_track(
            99, 'm-track-2', 'en', 'https://1.2.3.4/s.vtt');
    }

    /**
     * add_subtitle_track applies the SSRF allow-list to the .vtt URL.
     *
     * @covers \local_fastpix\service\upload_service
     */
    public function test_add_subtitle_track_blocks_ssrf_vtt(): void {
        $this->insert_ready_asset('m-track-3', 7);
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('add_media_track');
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\ssrf_blocked::class);
        upload_service::instance()->add_subtitle_track(
            7, 'm-track-3', 'en', 'http://localhost/s.vtt');
    }
}
