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

use local_fastpix\util\cache_keys;

/**
 * Boundary test for the 7-day soft-delete hard-purge (rule W10).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class purge_soft_deleted_assets_test extends \advanced_testcase {
    /** @var string */
    private const ASSET_TABLE = 'local_fastpix_asset';
    /** @var string */
    private const TRACK_TABLE = 'local_fastpix_track';
    /** @var int */
    private const DAY = 86400;
    /** @var int */
    private const HOUR = 3600;
    /** @var int */
    private const MINUTE = 60;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('local_fastpix', 'asset')->purge();
    }

    /**
     * Helper: insert asset.
     *
     * @param ?int $deletedat
     * @param ?string $playbackid
     * @return \stdClass
     */
    private function insert_asset(?int $deletedat, ?string $playbackid = null): \stdClass {
        global $DB;
        $now = time();
        $row = (object)[
            'fastpix_id'             => 'media-' . random_string(8),
            'playback_id'            => $playbackid,
            'owner_userid'           => 0,
            'title'                  => 'Test',
            'duration'               => null,
            'status'                 => 'ready',
            'access_policy'          => 'private',
            'drm_required'           => 0,
            'no_skip_required'       => 0,
            'has_captions'           => 0,
            'last_event_id'          => null,
            'last_event_at'          => null,
            'deleted_at'             => $deletedat,
            'gdpr_delete_pending_at' => null,
            'gdpr_delete_attempts'   => 0,
            'timecreated'            => $now - 30 * self::DAY,
            'timemodified'           => $now,
        ];
        $row->id = $DB->insert_record(self::ASSET_TABLE, $row);
        return $row;
    }

    /**
     * Helper: insert track.
     *
     * @param int $assetid
     * @return int
     */
    private function insert_track(int $assetid): int {
        global $DB;
        return (int)$DB->insert_record(self::TRACK_TABLE, (object)[
            'asset_id'     => $assetid,
            'track_kind'   => 'subtitle',
            'lang'         => 'en',
            'status'       => 'ready',
            'timemodified' => time(),
        ]);
    }

    /**
     * Helper: run task.
     */    private function run_task(): void {
        ob_start();
        (new purge_soft_deleted_assets())->execute();
        ob_end_clean();
}

    /**
     * Test that purges after 7 days 1 minute.
     *
     * @covers \local_fastpix\task\purge_soft_deleted_assets
     */
public function test_purges_after_7_days_1_minute(): void {
    global $DB;
    $row = $this->insert_asset(time() - 7 * self::DAY - self::MINUTE);
    $this->run_task();
    $this->assertFalse($DB->record_exists(self::ASSET_TABLE, ['id' => $row->id]));
}

    /**
     * Test that keeps at 6 days 23 hours.
     *
     * @covers \local_fastpix\task\purge_soft_deleted_assets
     */
public function test_keeps_at_6_days_23_hours(): void {
    global $DB;
    $row = $this->insert_asset(time() - 6 * self::DAY - 23 * self::HOUR);
    $this->run_task();
    $this->assertTrue($DB->record_exists(self::ASSET_TABLE, ['id' => $row->id]));
}

    /**
     * Test that keeps active assets with null deleted at.
     *
     * @covers \local_fastpix\task\purge_soft_deleted_assets
     */
public function test_keeps_active_assets_with_null_deleted_at(): void {
    global $DB;
    $row = $this->insert_asset(null);
    $this->run_task();
    $this->assertTrue($DB->record_exists(self::ASSET_TABLE, ['id' => $row->id]));
}

    /**
     * Test that cascade deletes local fastpix track rows.
     *
     * @covers \local_fastpix\task\purge_soft_deleted_assets
     */
public function test_cascade_deletes_local_fastpix_track_rows(): void {
    global $DB;
    $asset = $this->insert_asset(time() - 8 * self::DAY);
    $this->insert_track((int)$asset->id);
    $this->insert_track((int)$asset->id);

    $this->run_task();

    $this->assertSame(0, $DB->count_records(self::TRACK_TABLE, ['asset_id' => (int)$asset->id]));
    $this->assertFalse($DB->record_exists(self::ASSET_TABLE, ['id' => $asset->id]));
}

    /**
     * Test that invalidates both cache keys on purge.
     *
     * @covers \local_fastpix\task\purge_soft_deleted_assets
     */
public function test_invalidates_both_cache_keys_on_purge(): void {
    $asset = $this->insert_asset(time() - 8 * self::DAY, 'pb-purge-' . random_string(6));

    $cache = \cache::make('local_fastpix', 'asset');
    $fpkey = cache_keys::fastpix($asset->fastpix_id);
    $pbkey = cache_keys::playback($asset->playback_id);
    $cache->set($fpkey, (object)['stale' => true]);
    $cache->set($pbkey, (object)['stale' => true]);

    $this->run_task();

    $this->assertFalse($cache->get($fpkey));
    $this->assertFalse($cache->get($pbkey));
}

    /**
     * Test that batch caps per run and leaves remaining.
     *
     * @covers \local_fastpix\task\purge_soft_deleted_assets
     */
public function test_batch_caps_per_run_and_leaves_remaining(): void {
    global $DB;
    $reflect = new \ReflectionClass(purge_soft_deleted_assets::class);
    $batch = $reflect->getConstant('BATCH_SIZE');

    for ($i = 0; $i < $batch + 1; $i++) {
        $this->insert_asset(time() - 8 * self::DAY);
    }

    $this->run_task();

    $remaining = $DB->count_records_select(
        self::ASSET_TABLE,
        'deleted_at IS NOT NULL AND deleted_at < :cutoff',
        ['cutoff' => time() - 7 * self::DAY],
    );
    $this->assertSame(1, $remaining);
}
}
