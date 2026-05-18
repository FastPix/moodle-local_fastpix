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

namespace local_fastpix\hook;

/**
 * Regression test for the page-render hot path contract on
 * \local_fastpix\hook\after_config_callback.
 * Pins the invariant: the handler MUST NOT make synchronous HTTP calls
 * or touch the gateway. Injecting a trip-wire gateway whose every method
 * throws guarantees that any future change to the handler that invokes
 * the gateway directly OR indirectly will surface immediately.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class after_config_callback_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \local_fastpix\api\gateway::reset();
    }

    public function tearDown(): void {
        parent::tearDown();
        \local_fastpix\api\gateway::reset();
    }

    /**
     * Test that handle makes no gateway calls.
     *
     * @covers \local_fastpix\hook\after_config_callback
     */
    public function test_handle_makes_no_gateway_calls(): void {
        $tripwire = $this->createMock(\local_fastpix\api\gateway::class);
        $tripwire->method('health_probe')
            ->willThrowException(new \RuntimeException('FORBIDDEN: gateway from after_config'));
        $tripwire->method('get_media')
            ->willThrowException(new \RuntimeException('FORBIDDEN: gateway from after_config'));
        $tripwire->method('input_video_direct_upload')
            ->willThrowException(new \RuntimeException('FORBIDDEN: gateway from after_config'));
        $tripwire->method('media_create_from_url')
            ->willThrowException(new \RuntimeException('FORBIDDEN: gateway from after_config'));
        $tripwire->method('delete_media')
            ->willThrowException(new \RuntimeException('FORBIDDEN: gateway from after_config'));
        $tripwire->method('create_signing_key')
            ->willThrowException(new \RuntimeException('FORBIDDEN: gateway from after_config'));

        $reflect = new \ReflectionClass(\local_fastpix\api\gateway::class);
        $prop = $reflect->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $tripwire);

        $hook = $this->getMockBuilder(\core\hook\after_config::class)
            ->disableOriginalConstructor()
            ->getMock();

        after_config_callback::handle($hook);
        $this->addToAssertionCount(1);
    }

    /**
     * Test that handle returns in under 10 milliseconds.
     *
     * @covers \local_fastpix\hook\after_config_callback
     */
    public function test_handle_returns_in_under_10_milliseconds(): void {
        $hook = $this->getMockBuilder(\core\hook\after_config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $start = microtime(true);
        after_config_callback::handle($hook);
        $elapsedms = (microtime(true) - $start) * 1000.0;

        $this->assertLessThan(
            10.0,
            $elapsedms,
            sprintf('after_config_callback::handle took %.2f ms; budget is 10 ms', $elapsedms)
        );
    }
}
