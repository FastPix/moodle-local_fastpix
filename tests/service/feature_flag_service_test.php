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
 * Tests for the feature flag service.
 *
 * @covers \local_fastpix\service\feature_flag_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feature_flag_service_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        feature_flag_service::reset();
    }

    public function tearDown(): void {
        parent::tearDown();
        feature_flag_service::reset();
    }

    /**
     * Test that drm enabled with flag off returns false.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_drm_enabled_with_flag_off_returns_false(): void {
        set_config('feature_drm_enabled', 0, 'local_fastpix');
        set_config('drm_configuration_id', 'abc', 'local_fastpix');

        $this->assertFalse(feature_flag_service::instance()->drm_enabled());
    }

    /**
     * Test that drm enabled with empty config id returns false.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_drm_enabled_with_empty_config_id_returns_false(): void {
        set_config('feature_drm_enabled', 1, 'local_fastpix');
        set_config('drm_configuration_id', '', 'local_fastpix');

        $this->assertFalse(feature_flag_service::instance()->drm_enabled());
    }

    /**
     * Test that drm enabled with both set returns true.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_drm_enabled_with_both_set_returns_true(): void {
        set_config('feature_drm_enabled', 1, 'local_fastpix');
        set_config('drm_configuration_id', 'abc', 'local_fastpix');

        $this->assertTrue(feature_flag_service::instance()->drm_enabled());
    }

    /**
     * Test that watermark enabled returns config value.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_watermark_enabled_returns_config_value(): void {
        set_config('feature_watermark_enabled', 1, 'local_fastpix');
        $this->assertTrue(feature_flag_service::instance()->watermark_enabled());

        set_config('feature_watermark_enabled', 0, 'local_fastpix');
        $this->assertFalse(feature_flag_service::instance()->watermark_enabled());
    }

    /**
     * Test that tracking enabled returns config value.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_tracking_enabled_returns_config_value(): void {
        set_config('feature_tracking_enabled', 1, 'local_fastpix');
        $this->assertTrue(feature_flag_service::instance()->tracking_enabled());

        set_config('feature_tracking_enabled', 0, 'local_fastpix');
        $this->assertFalse(feature_flag_service::instance()->tracking_enabled());
    }

    /**
     * Test that drm configuration id returns null when empty.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_drm_configuration_id_returns_null_when_empty(): void {
        set_config('drm_configuration_id', '', 'local_fastpix');
        $this->assertNull(feature_flag_service::instance()->drm_configuration_id());

        set_config('drm_configuration_id', 'cfg-xyz', 'local_fastpix');
        $this->assertSame('cfg-xyz', feature_flag_service::instance()->drm_configuration_id());
    }

    /**
     * Test that snapshot returns full state.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_snapshot_returns_full_state(): void {
        set_config('feature_drm_enabled', 1, 'local_fastpix');
        set_config('drm_configuration_id', 'abc', 'local_fastpix');
        set_config('feature_watermark_enabled', 1, 'local_fastpix');
        set_config('feature_tracking_enabled', 0, 'local_fastpix');

        $snapshot = feature_flag_service::instance()->snapshot();

        $this->assertSame(
            ['drm' => true, 'watermark' => true, 'tracking' => false],
            $snapshot
        );
    }

    /**
     * Test that reset clears singleton instance.
     *
     * @covers \local_fastpix\service\feature_flag_service
     */
    public function test_reset_clears_singleton_instance(): void {
        $first = feature_flag_service::instance();
        $this->assertSame($first, feature_flag_service::instance());

        feature_flag_service::reset();

        $second = feature_flag_service::instance();
        $this->assertNotSame($first, $second);
    }
}
