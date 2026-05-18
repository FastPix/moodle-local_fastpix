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
 * Tests for the asset service.
 *
 * @covers \local_fastpix\service\asset_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class asset_service_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_asset';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('local_fastpix', 'asset')->purge();
        \local_fastpix\api\gateway::reset();
    }

    public function tearDown(): void {
        parent::tearDown();
        \local_fastpix\api\gateway::reset();
    }

    /**
     * Inject a mock into the gateway singleton's static $instance slot.
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
     * Helper: insert asset.
     *
     * @param array $overrides
     * @return \stdClass
     */
    private function insert_asset(array $overrides = []): \stdClass {
        global $DB;
        $now = time();
        $row = (object)array_merge([
            'fastpix_id'       => 'media-' . random_string(8),
            'playback_id'      => 'pb-' . random_string(8),
            'owner_userid'     => 0,
            'title'            => 'Test asset',
            'duration'         => 12.5,
            'status'           => 'ready',
            'access_policy'    => 'private',
            'drm_required'     => 0,
            'no_skip_required' => 0,
            'has_captions'     => 0,
            'last_event_id'    => null,
            'last_event_at'    => null,
            'deleted_at'       => null,
            'gdpr_delete_pending_at' => null,
            'timecreated'      => $now,
            'timemodified'     => $now,
        ], $overrides);
        $row->id = $DB->insert_record(self::TABLE, $row);
        return $row;
    }

    /**
     * Helper: gateway response.
     *
     * @param array $overrides
     * @return \stdClass
     */
    private function gateway_response(array $overrides = []): \stdClass {
        return (object)['data' => (object)array_merge([
            'id'           => 'media-remote-1',
            'title'        => 'Remote title',
            'duration'     => 30.5,
            'status'       => 'ready',
            'accessPolicy' => 'private',
            'playbackIds'  => [
                (object)['id' => 'pb-remote-1', 'accessPolicy' => 'private'],
            ],
        ], $overrides)];
    }

    // A. Read paths.

    /**
     * Test that get by fastpix id returns null when not found.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_returns_null_when_not_found(): void {
        $this->assertNull(asset_service::get_by_fastpix_id('does-not-exist'));
    }

    /**
     * Test that get by fastpix id caches db row.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_caches_db_row(): void {
        global $DB;
        $row = $this->insert_asset(['fastpix_id' => 'media-cache-1']);

        $first = asset_service::get_by_fastpix_id('media-cache-1');
        $this->assertNotNull($first);

        // Mutate the DB directly behind the cache. A cached read should still see.
        // The original title; a DB read would see the new one.
        $DB->set_field(self::TABLE, 'title', 'Mutated', ['id' => $row->id]);

        $second = asset_service::get_by_fastpix_id('media-cache-1');
        $this->assertSame('Test asset', $second->title);
    }

    /**
     * Test that get by fastpix id filters soft deleted by default.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_filters_soft_deleted_by_default(): void {
        $this->insert_asset(['fastpix_id' => 'media-soft-1', 'deleted_at' => time()]);
        $this->assertNull(asset_service::get_by_fastpix_id('media-soft-1'));
    }

    /**
     * Test that get by fastpix id with include deleted returns soft deleted.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_with_include_deleted_returns_soft_deleted(): void {
        $this->insert_asset(['fastpix_id' => 'media-soft-2', 'deleted_at' => time()]);
        $row = asset_service::get_by_fastpix_id('media-soft-2', true);
        $this->assertNotNull($row);
        $this->assertNotEmpty($row->deleted_at);
    }

    /**
     * Test that get by playback id returns row.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_playback_id_returns_row(): void {
        $this->insert_asset(['fastpix_id' => 'media-pb-1', 'playback_id' => 'pb-lookup']);
        $row = asset_service::get_by_playback_id('pb-lookup');
        $this->assertNotNull($row);
        $this->assertSame('media-pb-1', $row->fastpix_id);
    }

    /**
     * Test that get by id returns row no cache.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_id_returns_row_no_cache(): void {
        $inserted = $this->insert_asset();
        $row = asset_service::get_by_id($inserted->id);
        $this->assertNotNull($row);
        $this->assertSame($inserted->fastpix_id, $row->fastpix_id);
    }

    /**
     * Test that get by fastpix id warms playback id cache too.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_warms_playback_id_cache_too(): void {
        global $DB;
        $this->insert_asset(['fastpix_id' => 'media-warm-1', 'playback_id' => 'pb-warm-1']);

        // First read by fastpix_id — populates BOTH cache keys.
        asset_service::get_by_fastpix_id('media-warm-1');

        // Mutate DB to detect any DB hit.
        $DB->set_field(
            self::TABLE,
            'title',
            'WAS-MUTATED',
            ['fastpix_id' => 'media-warm-1']
        );

        // Now lookup by playback_id; should hit the warmed cache, not DB.
        $row = asset_service::get_by_playback_id('pb-warm-1');
        $this->assertSame('Test asset', $row->title);
    }

    // B. Lazy fetch.

    /**
     * Test that get by fastpix id or fetch returns existing row no gateway call.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_returns_existing_row_no_gateway_call(): void {
        $this->insert_asset(['fastpix_id' => 'media-existing']);

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('get_media');
        $this->inject_gateway_mock($mock);

        $row = asset_service::get_by_fastpix_id_or_fetch('media-existing');
        $this->assertSame('media-existing', $row->fastpix_id);
    }

    /**
     * Test that get by fastpix id or fetch calls gateway on cache and db miss.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_calls_gateway_on_cache_and_db_miss(): void {
        global $DB;

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->once())
            ->method('get_media')
            ->with('media-cold')
            ->willReturn($this->gateway_response(['id' => 'media-cold']));
        $this->inject_gateway_mock($mock);

        $row = asset_service::get_by_fastpix_id_or_fetch('media-cold');

        $this->assertSame('media-cold', $row->fastpix_id);
        $this->assertTrue($DB->record_exists(self::TABLE, ['fastpix_id' => 'media-cold']));
    }

    /**
     * Test that get by fastpix id or fetch inserts with sentinel owner userid zero.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_inserts_with_sentinel_owner_userid_zero(): void {
        global $DB;

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')->willReturn($this->gateway_response(['id' => 'media-sent']));
        $this->inject_gateway_mock($mock);

        asset_service::get_by_fastpix_id_or_fetch('media-sent');

        $stored = $DB->get_record(self::TABLE, ['fastpix_id' => 'media-sent']);
        $this->assertSame(0, (int)$stored->owner_userid);
    }

    /**
     * Test that get by fastpix id or fetch throws asset not found on gateway 404.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_throws_asset_not_found_on_gateway_404(): void {
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')
            ->willThrowException(new \local_fastpix\exception\gateway_not_found('media-404'));
        $this->inject_gateway_mock($mock);

        $this->expectException(\local_fastpix\exception\asset_not_found::class);
        asset_service::get_by_fastpix_id_or_fetch('media-404');
    }

    /**
     * Test that get by fastpix id or fetch extracts first private playback id.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_extracts_first_private_playback_id(): void {
        global $DB;

        $response = $this->gateway_response([
        'id'          => 'media-multi',
        'playbackIds' => [
            (object)['id' => 'pb-public-skip', 'accessPolicy' => 'public'],
            (object)['id' => 'pb-1', 'accessPolicy' => 'private'],
            (object)['id' => 'pb-also-private', 'accessPolicy' => 'private'],
        ],
        ]);
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')->willReturn($response);
        $this->inject_gateway_mock($mock);

        asset_service::get_by_fastpix_id_or_fetch('media-multi');
        $stored = $DB->get_record(self::TABLE, ['fastpix_id' => 'media-multi']);
        $this->assertSame('pb-1', $stored->playback_id);
    }

    /**
     * Test that get by fastpix id or fetch handles drm access policy.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_handles_drm_access_policy(): void {
        global $DB;

        $response = $this->gateway_response([
        'id'           => 'media-drm',
        'accessPolicy' => 'drm',
        'playbackIds'  => [
            (object)['id' => 'pb-drm', 'accessPolicy' => 'drm'],
        ],
        ]);
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')->willReturn($response);
        $this->inject_gateway_mock($mock);

        asset_service::get_by_fastpix_id_or_fetch('media-drm');
        $stored = $DB->get_record(self::TABLE, ['fastpix_id' => 'media-drm']);
        $this->assertSame('drm', $stored->access_policy);
        $this->assertSame(1, (int)$stored->drm_required);
    }

    // C. Race condition.

    /**
     * Test that get by fastpix id or fetch recovers from unique race.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_get_by_fastpix_id_or_fetch_recovers_from_unique_race(): void {
        global $DB;

        // Pre-insert: simulate a parallel worker that has already INSERTed.
        // We do NOT warm the cache — _or_fetch must miss cache, miss the.
        // Initial DB read (because the cache miss path also misses... no, wait).
        // Strategy: warm a cache "miss" by NOT pre-loading cache, but the.
        // Cache will be checked first; on miss it does $DB->get_record which.
        // WILL find the pre-existing row. So we need the gateway's INSERT.
        // Path to be exercised, which means the initial get_by_fastpix_id.
        // Must miss BOTH cache and DB.
        //
        // We can't truly simulate the race in-process. Instead, set up a.
        // Mock gateway whose get_media INSERTs a row as a side-effect (the.
        // "Parallel worker"). The subsequent INSERT inside _or_fetch then.
        // Collides with the UNIQUE constraint and triggers the recovery path.

        $selftest = $this;
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')->willReturnCallback(
            function ($fastpixid) use ($selftest) {
                $selftest->insert_asset([
                    'fastpix_id'  => $fastpixid,
                    'title'       => 'Race-winner row',
                    'playback_id' => 'pb-race',
                ]);
                return $selftest->gateway_response(['id' => $fastpixid]);
            }
        );
        $this->inject_gateway_mock($mock);

        $row = asset_service::get_by_fastpix_id_or_fetch('media-race');

        $this->assertSame('media-race', $row->fastpix_id);
        $this->assertSame('Race-winner row', $row->title);
        $this->assertSame(1, $DB->count_records(self::TABLE, ['fastpix_id' => 'media-race']));
    }

    // D. Soft delete.

    /**
     * Test that soft delete sets deleted at and invalidates cache.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_soft_delete_sets_deleted_at_and_invalidates_cache(): void {
        global $DB;
        $row = $this->insert_asset(['fastpix_id' => 'media-del', 'playback_id' => 'pb-del']);

        // Warm both cache keys.
        asset_service::get_by_fastpix_id('media-del');

        asset_service::soft_delete($row->id);

        $stored = $DB->get_record(self::TABLE, ['id' => $row->id]);
        $this->assertNotEmpty($stored->deleted_at);

        // Cache miss expected: ::get returns false on miss.
        $cache = \cache::make('local_fastpix', 'asset');
        $reflection = new \ReflectionClass(asset_service::class);

        $keyfpmethod = $reflection->getMethod('cache_key_fastpix');
        $keyfpmethod->setAccessible(true);
        $fpkey = $keyfpmethod->invoke(null, 'media-del');

        $keypbmethod = $reflection->getMethod('cache_key_playback');
        $keypbmethod->setAccessible(true);
        $pbkey = $keypbmethod->invoke(null, 'pb-del');

        $this->assertFalse($cache->get($fpkey));
        $this->assertFalse($cache->get($pbkey));
    }

    /**
     * Test that soft delete handles missing row silently.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_soft_delete_handles_missing_row_silently(): void {
        // Should not throw.
        asset_service::soft_delete(999999);
        $this->assertTrue(true);
    }

    // E. List operations.

    /**
     * Test that list for owner default returns only ready status.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_list_for_owner_default_returns_only_ready_status(): void {
        $this->insert_asset(['owner_userid' => 42, 'status' => 'ready', 'fastpix_id' => 'm-r1']);
        $this->insert_asset(['owner_userid' => 42, 'status' => 'preparing', 'fastpix_id' => 'm-p1']);
        $this->insert_asset(['owner_userid' => 99, 'status' => 'ready', 'fastpix_id' => 'm-other']);

        $rows = asset_service::list_for_owner(42);
        $this->assertCount(1, $rows);
        $this->assertSame('m-r1', $rows[0]->fastpix_id);
    }

    /**
     * Test that list for owner excludes soft deleted.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_list_for_owner_excludes_soft_deleted(): void {
        $this->insert_asset(['owner_userid' => 7, 'fastpix_id' => 'm-live']);
        $this->insert_asset(['owner_userid' => 7, 'fastpix_id' => 'm-dead', 'deleted_at' => time()]);

        $rows = asset_service::list_for_owner(7);
        $this->assertCount(1, $rows);
        $this->assertSame('m-live', $rows[0]->fastpix_id);
    }

    /**
     * Test that list for owner paginated with search filter.
     *
     * @covers \local_fastpix\service\asset_service
     */
    public function test_list_for_owner_paginated_with_search_filter(): void {
        $this->insert_asset(['owner_userid' => 5, 'title' => 'Lecture 1', 'fastpix_id' => 'm-l1']);
        $this->insert_asset(['owner_userid' => 5, 'title' => 'Lecture 2', 'fastpix_id' => 'm-l2']);
        $this->insert_asset(['owner_userid' => 5, 'title' => 'Workshop', 'fastpix_id' => 'm-w']);

        $rows = asset_service::list_for_owner_paginated(5, 'ready', 0, 50, 'Lecture');
        $this->assertCount(2, $rows);
        foreach ($rows as $r) {
            $this->assertStringStartsWith('Lecture', $r->title);
        }
    }
}
