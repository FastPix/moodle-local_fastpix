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

namespace local_fastpix\health;

/**
 * Tests for the public health endpoint runner (C2).
 * The thin shim at health.php is not directly testable (procedural script
 * that emits headers + dies). Tests exercise \local_fastpix\health\runner
 * with the gateway and rate-limiter as injectable dependencies via the
 * static-instance reflection pattern used elsewhere in this suite.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class health_endpoint_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \local_fastpix\api\gateway::reset();
        \local_fastpix\service\rate_limiter_service::reset();
        \cache::make('local_fastpix', 'rate_limit')->purge();
    }

    public function tearDown(): void {
        parent::tearDown();
        \local_fastpix\api\gateway::reset();
        \local_fastpix\service\rate_limiter_service::reset();
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
     * Test that health returns 200 and ok status on probe success.
     *
     * @covers \local_fastpix\health\runner
     */
    public function test_health_returns_200_and_ok_status_on_probe_success(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('health_probe')->willReturn(true);
        $this->inject_gateway_mock($mock);

        $result = runner::run('1.2.3.4');

        $this->assertSame(200, $result['http_code']);
        $this->assertSame('ok', $result['body']['status']);
        $this->assertTrue($result['body']['fastpix_reachable']);
        $this->assertIsInt($result['body']['latency_ms']);
        $this->assertGreaterThanOrEqual(0, $result['body']['latency_ms']);
        $this->assertIsInt($result['body']['timestamp']);
    }

    /**
     * Test that health returns 503 when probe fails.
     *
     * @covers \local_fastpix\health\runner
     */
    public function test_health_returns_503_when_probe_fails(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('health_probe')->willReturn(false);
        $this->inject_gateway_mock($mock);

        $result = runner::run('1.2.3.4');

        $this->assertSame(503, $result['http_code']);
        $this->assertSame('degraded', $result['body']['status']);
        $this->assertFalse($result['body']['fastpix_reachable']);
    }

    /**
     * Test that health returns 503 and does not throw on gateway exception.
     *
     * @covers \local_fastpix\health\runner
     */
    public function test_health_returns_503_and_does_not_throw_on_gateway_exception(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('health_probe')
            ->willThrowException(new \RuntimeException('simulated failure'));
        $this->inject_gateway_mock($mock);

        // Must not throw; must return a valid response.
        $result = runner::run('1.2.3.4');

        $this->assertSame(503, $result['http_code']);
        $this->assertSame('error', $result['body']['status']);
        $this->assertFalse($result['body']['fastpix_reachable']);

        // The catch block emits a debugging() call.
        $this->assertDebuggingCalled();
    }

    /**
     * Test that health rate limits after 30 requests per minute.
     *
     * @covers \local_fastpix\health\runner
     */
    public function test_health_rate_limits_after_30_requests_per_minute(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('health_probe')->willReturn(true);
        $this->inject_gateway_mock($mock);

        // 30 Successful requests.
        for ($i = 0; $i < 30; $i++) {
            $r = runner::run('5.5.5.5');
            $this->assertSame(200, $r['http_code'], "request {$i} should pass under 30/min cap");
        }

        // 31St triggers the rate limit.
        $r = runner::run('5.5.5.5');
        $this->assertSame(429, $r['http_code']);
        $this->assertSame('rate_limited', $r['body']['status']);
        $this->assertNull($r['body']['fastpix_reachable']);
    }

    /**
     * Test that health response body shape is stable.
     *
     * @covers \local_fastpix\health\runner
     */
    public function test_health_response_body_shape_is_stable(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('health_probe')->willReturn(true);
        $this->inject_gateway_mock($mock);

        $result = runner::run('1.2.3.4');
        $body = $result['body'];

        // Exact key set — protects ops dashboards that grep specific keys.
        $this->assertSame(
            ['status', 'fastpix_reachable', 'latency_ms', 'timestamp'],
            array_keys($body),
        );
    }
}
