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

namespace local_fastpix\task;

/**
 * Boundary test for the GDPR-delete retry cap (T3.5).
 * The retry task increments gdpr_delete_attempts on each run. After
 * MAX_ATTEMPTS (10) the row is no longer selected by the task — a
 * stuck-at-cap row signals to ops that something is wrong on the FastPix
 * side, but the local soft-delete is still in effect, so user data is
 * not visible to anyone.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class retry_gdpr_delete_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_asset';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \local_fastpix\api\gateway::reset();
        \cache::make('local_fastpix', 'asset')->purge();
    }

    public function tearDown(): void {
        parent::tearDown();
        \local_fastpix\api\gateway::reset();
    }

    /**
     * Helper: inject failing gateway.
     */    private function inject_failing_gateway(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('delete_media')
            ->willThrowException(new \local_fastpix\exception\gateway_unavailable('500:simulated'));

        $reflection = new \ReflectionClass(\local_fastpix\api\gateway::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $mock);
}

    /**
     * Helper: insert pending asset.
     *
     * @param int $attempts
     * @return \stdClass
     */
private function insert_pending_asset(int $attempts = 0): \stdClass {
    global $DB;
    $now = time();
    $row = (object)[
        'fastpix_id'             => 'media-' . random_string(8),
        'playback_id'            => null,
        'owner_userid'           => 0,
        'title'                  => 'Stuck row',
        'duration'               => null,
        'status'                 => 'ready',
        'access_policy'          => 'private',
        'drm_required'           => 0,
        'no_skip_required'       => 0,
        'has_captions'           => 0,
        'last_event_id'          => null,
        'last_event_at'          => null,
        'deleted_at'             => $now,
        'gdpr_delete_pending_at' => $now,
        'gdpr_delete_attempts'   => $attempts,
        'timecreated'            => $now,
        'timemodified'           => $now,
    ];
    $row->id = $DB->insert_record(self::TABLE, $row);
    return $row;
}

    /**
     * Test that attempts increments on each failure.
     *
     * @covers \local_fastpix\task\retry_gdpr_delete
     */
public function test_attempts_increments_on_each_failure(): void {
    global $DB;
    $this->inject_failing_gateway();

    $asset = $this->insert_pending_asset(0);

    ob_start();
    (new retry_gdpr_delete())->execute();
    ob_end_clean();

    $stored = $DB->get_record(self::TABLE, ['id' => $asset->id]);
    $this->assertSame(1, (int)$stored->gdpr_delete_attempts);
    $this->assertNotEmpty(
        $stored->gdpr_delete_pending_at,
        'pending flag must remain set on failure'
    );
}

    /**
     * Test that row at attempt 9 is still processed.
     *
     * @covers \local_fastpix\task\retry_gdpr_delete
     */
public function test_row_at_attempt_9_is_still_processed(): void {
    global $DB;
    $this->inject_failing_gateway();

    $asset = $this->insert_pending_asset(9);

    ob_start();
    (new retry_gdpr_delete())->execute();
    $output = ob_get_clean();

    $stored = $DB->get_record(self::TABLE, ['id' => $asset->id]);
    $this->assertSame(
        10,
        (int)$stored->gdpr_delete_attempts,
        'attempts must increment from 9 to 10'
    );
    $this->assertStringContainsString(
        'CRITICAL',
        $output,
        'reaching MAX_ATTEMPTS must emit a CRITICAL log line'
    );
}

    /**
     * Test that row at cap is not reprocessed.
     *
     * @covers \local_fastpix\task\retry_gdpr_delete
     */
public function test_row_at_cap_is_not_reprocessed(): void {
    global $DB;
    $this->inject_failing_gateway();

    $asset = $this->insert_pending_asset(10); // Already at cap.

    ob_start();
    (new retry_gdpr_delete())->execute();
    ob_end_clean();

    $stored = $DB->get_record(self::TABLE, ['id' => $asset->id]);
    $this->assertSame(
        10,
        (int)$stored->gdpr_delete_attempts,
        'attempts must NOT increment past cap'
    );
}

    /**
     * Test that success clears pending flag.
     *
     * @covers \local_fastpix\task\retry_gdpr_delete
     */
public function test_success_clears_pending_flag(): void {
    global $DB;

    // Delete_media is declared : void — leave the mock without a.
    // Return spec so the default void return is used.
    $mock = $this->createMock(\local_fastpix\api\gateway::class);
    $reflection = new \ReflectionClass(\local_fastpix\api\gateway::class);
    $prop = $reflection->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, $mock);

    $asset = $this->insert_pending_asset(3);

    ob_start();
    (new retry_gdpr_delete())->execute();
    ob_end_clean();

    $stored = $DB->get_record(self::TABLE, ['id' => $asset->id]);
    $this->assertNull(
        $stored->gdpr_delete_pending_at,
        'pending flag must be cleared on remote-delete success'
    );
}
}
