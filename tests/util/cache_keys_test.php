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

namespace local_fastpix\util;

/**
 * Tests for the cache_keys utility.
 *
 * @covers \local_fastpix\util\cache_keys
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cache_keys_test extends \advanced_testcase {
    /**
     * Test that fastpix key is prefixed and 32 hex.
     *
     * @covers \local_fastpix\util\cache_keys
     */
    public function test_fastpix_key_is_prefixed_and_32_hex(): void {
        $key = cache_keys::fastpix('media-1');
        $this->assertStringStartsWith('fp_', $key);
        $this->assertSame(35, strlen($key)); // Fp_ + 32 hex.
        $this->assertMatchesRegularExpression('/^fp_[a-f0-9]{32}$/', $key);
    }

    /**
     * Test that playback key is prefixed and 32 hex.
     *
     * @covers \local_fastpix\util\cache_keys
     */
    public function test_playback_key_is_prefixed_and_32_hex(): void {
        $key = cache_keys::playback('pb-1');
        $this->assertStringStartsWith('pb_', $key);
        $this->assertMatchesRegularExpression('/^pb_[a-f0-9]{32}$/', $key);
    }

    /**
     * Test that fastpix and playback with same input differ.
     *
     * @covers \local_fastpix\util\cache_keys
     */
    public function test_fastpix_and_playback_with_same_input_differ(): void {
        $this->assertNotSame(
            cache_keys::fastpix('same-id'),
            cache_keys::playback('same-id')
        );
    }

    /**
     * Test that stable across calls.
     *
     * @covers \local_fastpix\util\cache_keys
     */
    public function test_stable_across_calls(): void {
        $this->assertSame(cache_keys::fastpix('x'), cache_keys::fastpix('x'));
        $this->assertSame(cache_keys::playback('x'), cache_keys::playback('x'));
    }
}
