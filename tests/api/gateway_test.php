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

namespace local_fastpix\api;

use GuzzleHttp\Psr7\Response;
use local_fastpix\service\credential_service;

/**
 * Tests for the gateway.
 *
 * @covers \local_fastpix\api\gateway
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class gateway_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        gateway::reset();
        \cache::make('local_fastpix', 'circuit_breaker')->purge();
        set_config('fastpix_base_url', 'https://api.fastpix.io', 'local_fastpix');
        set_config('version', '2026050401', 'local_fastpix');
    }

    public function tearDown(): void {
        parent::tearDown();
        gateway::reset();
    }

    /**
     * Build a gateway with mocked http_client and credential_service.
     * Constructor is private; use reflection to bypass it.
     *
     * @param mixed $httpmock
     * @param mixed $credentialmock
     * @return gateway
     */
    private function build_gateway($httpmock, $credentialmock = null): gateway {
        if ($credentialmock === null) {
            $credentialmock = $this->createMock(credential_service::class);
            $credentialmock->method('apikey')->willReturn('test-key');
            $credentialmock->method('apisecret')->willReturn('test-secret');
        }

        $reflection = new \ReflectionClass(gateway::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $httpprop = $reflection->getProperty('http');
        $httpprop->setAccessible(true);
        $httpprop->setValue($instance, $httpmock);

        $breakerprop = $reflection->getProperty('breakercache');
        $breakerprop->setAccessible(true);
        $breakerprop->setValue($instance, \cache::make('local_fastpix', 'circuit_breaker'));

        $credprop = $reflection->getProperty('credentials');
        $credprop->setAccessible(true);
        $credprop->setValue($instance, $credentialmock);

        return $instance;
    }

    /**
     * Helper: http mock returning.
     *
     * @param array $responses
     */
    private function http_mock_returning(array $responses) {
        $mock = $this->createMock(\core\http_client::class);
        $mock->method('request')->willReturnOnConsecutiveCalls(...$responses);
        return $mock;
    }

    // Get_media.

    /**
     * Test that get media happy returns decoded body.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_get_media_happy_returns_decoded_body(): void {
        $http = $this->http_mock_returning([
        new Response(200, [], json_encode(['id' => 'abc', 'status' => 'ready'])),
        ]);
        $gateway = $this->build_gateway($http);

        $result = $gateway->get_media('abc');

        $this->assertSame('abc', $result->id);
        $this->assertSame('ready', $result->status);
    }

    /**
     * Test that get media 404 throws gateway not found immediately no retry.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_get_media_404_throws_gateway_not_found_immediately_no_retry(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->willReturn(new Response(404, [], ''));

        $gateway = $this->build_gateway($http);

        $this->expectException(\local_fastpix\exception\gateway_not_found::class);
        $gateway->get_media('missing');
    }

    /**
     * Test that get media 500 retries three times then throws gateway unavailable.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_get_media_500_retries_three_times_then_throws_gateway_unavailable(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturn(new Response(500, [], ''));

        $gateway = $this->build_gateway($http);

        $this->expectException(\local_fastpix\exception\gateway_unavailable::class);
        $gateway->get_media('abc');
    }

    // Delete_media.

    /**
     * Test that delete media 404 returns silently.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_delete_media_404_returns_silently(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->willReturn(new Response(404, [], ''));

        $gateway = $this->build_gateway($http);

        $gateway->delete_media('missing');
        $this->assertTrue(true); // No exception.
    }

    /**
     * Test that delete media 2xx succeeds with idempotency key header.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_delete_media_2xx_succeeds_with_idempotency_key_header(): void {
        $capturedoptions = null;
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                $this->stringContains('/v1/on-demand/abc'),
                $this->callback(function ($options) use (&$capturedoptions) {
                    $capturedoptions = $options;
                    return isset($options['headers']['Idempotency-Key'])
                    && strlen($options['headers']['Idempotency-Key']) === 64;
                })
            )
            ->willReturn(new Response(204, [], ''));

        $this->build_gateway($http)->delete_media('abc');
        $this->assertNotNull($capturedoptions);
    }

    // Input_video_direct_upload.

    /**
     * Test that input video direct upload includes idempotency key on post.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_input_video_direct_upload_includes_idempotency_key_on_post(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('/v1/on-demand/upload'),
                $this->callback(fn($o) => isset($o['headers']['Idempotency-Key']))
            )
            ->willReturn(new Response(200, [], json_encode(['data' => ['uploadId' => 'u1']])));

        $this->build_gateway($http)->input_video_direct_upload('owner-hash', [], 'private', null);
    }

    // 429 retry-after.

    /**
     * Test that 429 honors retry after header clamped to 3000ms.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_429_honors_retry_after_header_clamped_to_3000ms(): void {
        // Two responses: 429 with Retry-After=10 (clamp would be 3s; we just verify the.
        // Retry loop continues, not the exact wall-clock delay), then 200.
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                new Response(429, ['Retry-After' => '10'], ''),
                new Response(200, [], json_encode(['ok' => true])),
            );

        $start = microtime(true);
        $result = $this->build_gateway($http)->get_media('abc');
        $elapsedms = (microtime(true) - $start) * 1000;

        $this->assertTrue($result->ok);
        // 10S would be ~10000ms; clamp must keep us well under 5s.
        $this->assertLessThan(5000, $elapsedms, 'Retry-After was not clamped');
    }

    // 4xx non-retryable.

    /**
     * Test that 400 throws immediately without retry.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_400_throws_immediately_without_retry(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->willReturn(new Response(400, [], ''));

        $this->expectException(\local_fastpix\exception\gateway_unavailable::class);
        $this->build_gateway($http)->input_video_direct_upload('h', [], 'private', null);
    }

    // Circuit breaker.

    /**
     * Test that circuit breaker opens after 5 consecutive failures.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_circuit_breaker_opens_after_5_consecutive_failures(): void {
        // Use 400 (non-retryable) so each call is one HTTP request and breaker_record_failure fires.
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(5))
            ->method('request')
            ->willReturn(new Response(400, [], ''));

        $gateway = $this->build_gateway($http);

        for ($i = 0; $i < 5; $i++) {
            try {
                $gateway->input_video_direct_upload('owner', [], 'private', null);
                $this->fail('expected gateway_unavailable on attempt ' . $i);
            } catch (\local_fastpix\exception\gateway_unavailable $e) {
                unset($e); // Expected — verified by reaching this branch.
            }
        }

        // Inspect breaker state: should be open. Cache key is the SHA-256-32.
        // Hash of method:path (MUC area uses simplekeys=true). Per T1.1.
        // (REVIEW S-1) — was crc32b, now sha256/32 to avoid 32-bit collisions.
        $breaker = \cache::make('local_fastpix', 'circuit_breaker');
        $state = $breaker->get(substr(hash('sha256', 'POST:/v1/on-demand/upload'), 0, 32));
        $this->assertIsArray($state);
        $this->assertGreaterThanOrEqual(5, $state['failures']);
        $this->assertGreaterThan(time(), $state['open_until']);
    }

    /**
     * Test that circuit breaker open short circuits with gateway unavailable.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_circuit_breaker_open_short_circuits_with_gateway_unavailable(): void {
        // Pre-load breaker as open. Cache key matches the gateway's hashing scheme.
        // (simplekeys=true on MUC area 'circuit_breaker'). Per T1.1 — sha256/32.
        // Replaced crc32b after REVIEW S-1.
        $breaker = \cache::make('local_fastpix', 'circuit_breaker');
        $key = substr(hash('sha256', 'GET:/v1/on-demand/' . rawurlencode('any')), 0, 32);
        $breaker->set($key, [
        'failures' => 5,
        'open_until' => time() + 30,
        ]);

        // Mock must NOT receive a request call.
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->never())->method('request');

        $gateway = $this->build_gateway($http);

        try {
            $gateway->get_media('any');
            $this->fail('expected gateway_unavailable');
        } catch (\local_fastpix\exception\gateway_unavailable $e) {
            $this->assertStringContainsString('circuit_open', $e->getMessage() . ' ' . (string)$e->a);
        }
    }

    // Health_probe.

    /**
     * Test that health probe returns true on 2xx.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_health_probe_returns_true_on_2xx(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(new Response(200, [], '{}'));
        $this->assertTrue($this->build_gateway($http)->health_probe());
    }

    /**
     * Test that health probe returns false on 5xx.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_health_probe_returns_false_on_5xx(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(new Response(503, [], ''));
        $this->assertFalse($this->build_gateway($http)->health_probe());
    }

    /**
     * Test that health probe returns false on network exception never throws.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_health_probe_returns_false_on_network_exception_never_throws(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willThrowException(new \RuntimeException('connect failed'));

        $result = $this->build_gateway($http)->health_probe();
        $this->assertFalse($result);
    }

    // Timeout profiles.

    /**
     * Test that get media uses profile hot 3s timeouts.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_get_media_uses_profile_hot_3s_timeouts(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->anything(),
                $this->callback(fn($o) => $o['connect_timeout'] === 3 && $o['timeout'] === 3)
            )
            ->willReturn(new Response(200, [], '{}'));

        $this->build_gateway($http)->get_media('abc');
    }

    /**
     * Test that input video direct upload uses profile standard 5s 30s timeouts.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_input_video_direct_upload_uses_profile_standard_5s_30s_timeouts(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(fn($o) => $o['connect_timeout'] === 5 && $o['timeout'] === 30)
            )
            ->willReturn(new Response(200, [], json_encode(['data' => []])));

        $this->build_gateway($http)->input_video_direct_upload('h', [], 'private', null);
    }

    // Redaction canary.

    /**
     * Test that request logs no apikey apisecret or jwt pattern.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_request_logs_no_apikey_apisecret_or_jwt_pattern(): void {
        $cred = $this->createMock(credential_service::class);
        $cred->method('apikey')->willReturn('apikey-VERY-SECRET-VALUE');
        $cred->method('apisecret')->willReturn('apisecret-EVEN-MORE-SECRET');

        $http = $this->createMock(\core\http_client::class);
        // Body could plausibly contain a JWT-shaped string; ensure it's not logged.
        $http->method('request')->willReturn(
            new Response(200, [], json_encode(['token' => 'eyJabcdefghijklmnopqr']))
        );

        $tmp = tempnam(sys_get_temp_dir(), 'gwlog_');
        $original = ini_get('error_log');
        ini_set('error_log', $tmp);

        try {
            $this->build_gateway($http, $cred)->get_media('abc');
            $logbuffer = (string)file_get_contents($tmp);
        } finally {
            ini_set('error_log', $original);
            @unlink($tmp);
        }

        $this->assertStringNotContainsString('apikey-VERY-SECRET-VALUE', $logbuffer);
        $this->assertStringNotContainsString('apisecret-EVEN-MORE-SECRET', $logbuffer);
        $this->assertDoesNotMatchRegularExpression('/eyJ[A-Za-z0-9_-]{10,}/', $logbuffer);

        // M2 — new structured fields must be present.
        $this->assertStringContainsString('"request_id":"req_', $logbuffer);
        $this->assertStringContainsString('"method":"GET"', $logbuffer);
        $this->assertStringContainsString('"host":"api.fastpix.io"', $logbuffer);
        $this->assertMatchesRegularExpression('#"path":"\\\\?/v1\\\\?/on-demand\\\\?/abc"#', $logbuffer);
    }

    /**
     * Test that request id propagated as x request id header.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_request_id_propagated_as_x_request_id_header(): void {
        $captured = null;
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->anything(),
                $this->callback(function ($opts) use (&$captured) {
                    $captured = $opts['headers']['X-Request-Id'] ?? null;
                    return true;
                })
            )
            ->willReturn(new Response(200, [], json_encode(['id' => 'x'])));

        $this->build_gateway($http)->get_media('x');

        $this->assertNotNull($captured);
        $this->assertMatchesRegularExpression('/^req_[A-Za-z0-9]{12}$/', (string)$captured);
    }

    /**
     * Test that response over 5mib throws gateway invalid response.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_response_over_5mib_throws_gateway_invalid_response(): void {
        $oversize = 5 * 1024 * 1024 + 1;
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(
            new Response(200, ['Content-Length' => (string)$oversize], json_encode(['id' => 'big']))
        );

        $this->expectException(\local_fastpix\exception\gateway_invalid_response::class);
        $this->expectExceptionMessage('response_too_large');
        $this->build_gateway($http)->get_media('asset-big');
    }

    /**
     * Test that 408 request timeout is retried.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_408_request_timeout_is_retried(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturn(new Response(408, [], 'timeout'));

        $gateway = $this->build_gateway($http);

        $this->expectException(\local_fastpix\exception\gateway_unavailable::class);
        $this->expectExceptionMessageMatches('/retries_exhausted/');
        $gateway->get_media('asset-408');
    }

    /**
     * Test that breaker state visible across workers.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_breaker_state_visible_across_workers(): void {
        // Worker A — same endpoint 5 times so the per-endpoint counter trips.
        $httpa = $this->createMock(\core\http_client::class);
        $httpa->method('request')->willReturn(new Response(400, [], 'bad request'));
        $gatewaya = $this->build_gateway($httpa);
        for ($i = 0; $i < 5; $i++) {
            try {
                $gatewaya->delete_media('asset-same');
            } catch (\Throwable $e) {
                // Expected exception verified by the throw.
                unset($e);
            }
        }

        // Worker B — separate gateway instance, separate http mock; breaker.
        // State is MUC-backed (W8) so worker B must see open and short-circuit.
        gateway::reset();
        $httpb = $this->createMock(\core\http_client::class);
        $httpb->expects($this->never())->method('request');
        $gatewayb = $this->build_gateway($httpb);

        $this->expectException(\local_fastpix\exception\gateway_unavailable::class);
        $this->expectExceptionMessageMatches('/circuit_open:/');
        $gatewayb->delete_media('asset-same');
    }

    /**
     * Test that decode body empty response returns empty object.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_decode_body_empty_response_returns_empty_object(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(new Response(200, [], ''));
        $result = $this->build_gateway($http)->get_media('empty');
        $this->assertEquals(new \stdClass(), $result);
    }

    /**
     * Test that decode body invalid json throws gateway invalid response.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_decode_body_invalid_json_throws_gateway_invalid_response(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(new Response(200, [], 'totally not json'));
        $this->expectException(\local_fastpix\exception\gateway_invalid_response::class);
        $this->expectExceptionMessage('json_decode_failed');
        $this->build_gateway($http)->get_media('bad-json');
    }

    /**
     * Test that decode body array response wrapped in data object.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_decode_body_array_response_wrapped_in_data_object(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(new Response(200, [], json_encode(['a', 'b'])));
        $result = $this->build_gateway($http)->get_media('arr');
        $this->assertIsArray($result->data);
        $this->assertSame(['a', 'b'], $result->data);
    }

    /**
     * Test that 429 with missing retry after falls back to default.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_429_with_missing_retry_after_falls_back_to_default(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                new Response(429, [], ''),
                new Response(200, [], json_encode(['ok' => true])),
            );
        $result = $this->build_gateway($http)->get_media('ra-missing');
        $this->assertTrue((bool)($result->ok ?? false));
    }

    /**
     * Test that 429 with non digit retry after falls back to default.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_429_with_non_digit_retry_after_falls_back_to_default(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                new Response(429, ['Retry-After' => 'Sat, 01 Jan 2030 00:00:00 GMT'], ''),
                new Response(200, [], json_encode(['ok' => true])),
            );
        $result = $this->build_gateway($http)->get_media('ra-date');
        $this->assertTrue((bool)($result->ok ?? false));
    }

    /**
     * Test that body snippet truncates long response bodies in exception.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_body_snippet_truncates_long_response_bodies_in_exception(): void {
        $long = str_repeat('x', 1200);
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willReturn(new Response(400, [], $long));
        try {
            $this->build_gateway($http)->delete_media('long-err');
            $this->fail('expected gateway_unavailable');
        } catch (\local_fastpix\exception\gateway_unavailable $e) {
            $context = (string)$e->a;
            $this->assertStringContainsString('...', $context);
            $this->assertLessThan(900, strlen($context));
        }
    }

    /**
     * Test that create signing key posts to iam endpoint with idempotency key.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_create_signing_key_posts_to_iam_endpoint_with_idempotency_key(): void {
        $captured = null;
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('/v1/iam/signing-keys'),
                $this->callback(function ($opts) use (&$captured) {
                    $captured = $opts;
                    return isset($opts['headers']['Idempotency-Key']);
                })
            )
            ->willReturn(new Response(201, [], json_encode([
            'id'         => 'kid-new',
            'privateKey' => 'BASE64_PEM',
        ])));

        $result = $this->build_gateway($http)->create_signing_key();
        $this->assertSame('kid-new', $result->id);
        $this->assertSame(64, strlen($captured['headers']['Idempotency-Key']));
    }

    /**
     * Test that delete signing key targets iam endpoint and 404 is silent.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_delete_signing_key_targets_iam_endpoint_and_404_is_silent(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with('DELETE', $this->stringContains('/v1/iam/signing-keys/kid-1'), $this->anything())
            ->willReturn(new Response(204, [], ''));
        $this->build_gateway($http)->delete_signing_key('kid-1');
        $this->addToAssertionCount(1);

        // 404 Must be silent for DELETE — idempotent success.
        $http2 = $this->createMock(\core\http_client::class);
        $http2->method('request')->willReturn(new Response(404, [], ''));
        $this->build_gateway($http2)->delete_signing_key('kid-gone');
        $this->addToAssertionCount(1);
    }

    /**
     * Test that media create from url posts to on demand.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_media_create_from_url_posts_to_on_demand(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('/v1/on-demand'),
                $this->callback(fn($o) =>
                    is_array($o['json'])
                    && $o['json']['inputs'][0]['url'] === 'https://example.com/v.mp4'
                    && $o['json']['accessPolicy'] === 'public')
            )
            ->willReturn(new Response(201, [], json_encode([
            'data' => ['id' => 'm-url-1'],
        ])));

        $result = $this->build_gateway($http)->media_create_from_url(
            'https://example.com/v.mp4',
            'owner-hash',
            [],
            'public',
            null
        );
        $this->assertSame('m-url-1', $result->data->id);
    }

    /**
     * Test that media create from url attaches drm config when drm.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_media_create_from_url_attaches_drm_config_when_drm(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(fn($o) =>
                    is_array($o['json'])
                    && ($o['json']['drmConfigurationId'] ?? null) === 'drm-cfg-123')
            )
            ->willReturn(new Response(201, [], json_encode(['data' => ['id' => 'm-drm-1']])));

        $this->build_gateway($http)->media_create_from_url(
            'https://example.com/v.mp4',
            'owner-hash',
            [],
            'drm',
            'drm-cfg-123'
        );
    }

    /**
     * Test that health probe returns false on throwing http client.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_health_probe_returns_false_on_throwing_http_client(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')->willThrowException(new \RuntimeException('network down'));
        $this->assertFalse($this->build_gateway($http)->health_probe());
    }

    /**
     * Test that network exception logs attempt with status code zero.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_network_exception_logs_attempt_with_status_code_zero(): void {
        $http = $this->createMock(\core\http_client::class);
        $http->method('request')
            ->willThrowException(new \RuntimeException('network down'));

        $tmp = tempnam(sys_get_temp_dir(), 'gwlog_');
        $original = ini_get('error_log');
        ini_set('error_log', $tmp);
        try {
            try {
                $this->build_gateway($http)->get_media('x');
                $this->fail('expected gateway_unavailable');
            } catch (\local_fastpix\exception\gateway_unavailable $e) {
                $this->assertStringContainsString('network_RuntimeException', (string)$e->a);
            }
            $log = (string)file_get_contents($tmp);
        } finally {
            ini_set('error_log', $original);
            @unlink($tmp);
        }
        $this->assertStringContainsString('"status_code":0', $log);
    }

    /**
     * Test that breaker recovers after successful request.
     *
     * @covers \local_fastpix\api\gateway
     */
    public function test_breaker_recovers_after_successful_request(): void {
        // Mix of failures then a success — success must clear the breaker.
        $http = $this->createMock(\core\http_client::class);
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                new Response(400, [], 'fail'),
                new Response(204, [], ''),
            );
        $gateway = $this->build_gateway($http);

        try {
            $gateway->delete_media('flaky');
        } catch (\Throwable $e) {
            // Expected exception verified by the throw.
            unset($e);
        }
        // Same endpoint; second call succeeds — breaker counter must NOT trip.
        $gateway->delete_media('flaky');

        $breaker = \cache::make('local_fastpix', 'circuit_breaker');
        $state = $breaker->get(substr(hash('sha256', 'DELETE:/v1/on-demand/flaky'), 0, 32));
        $this->assertFalse($state, 'breaker_record_success must clear the cache entry');
    }
}
