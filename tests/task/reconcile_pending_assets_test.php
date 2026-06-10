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

/**
 * Tests for the reconcile_pending_assets task (missed-webhook backfill, Issue 2).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Asserts the reconciler flips stuck assets to the authoritative FastPix
 * status, leaves still-processing ones alone, and defers on outage.
 *
 * @covers \local_fastpix\task\reconcile_pending_assets
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class reconcile_pending_assets_test extends \advanced_testcase {
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
     * Install a gateway mock as the singleton.
     *
     * @param mixed $mock
     */
    private function set_gateway($mock): void {
        $prop = (new \ReflectionClass(\local_fastpix\api\gateway::class))->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $mock);
    }

    /**
     * Insert an asset.
     *
     * @param string $status
     * @param int $age Seconds since timemodified.
     * @param string $fastpixid
     * @return \stdClass
     */
    private function insert_asset(string $status, int $age, string $fastpixid = ''): \stdClass {
        global $DB;
        $now = time();
        $fastpixid = $fastpixid !== '' ? $fastpixid : 'media-' . random_string(8);
        $row = (object)[
            'fastpix_id'    => $fastpixid,
            'playback_id'   => null,
            'owner_userid'  => 0,
            'title'         => 'T',
            'status'        => $status,
            'access_policy' => 'private',
            'drm_required'  => 0,
            'no_skip_required' => 0,
            'has_captions'  => 0,
            'last_event_at' => $now - $age,
            'deleted_at'    => null,
            'timecreated'   => $now - $age,
            'timemodified'  => $now - $age,
        ];
        $row->id = $DB->insert_record(self::TABLE, $row);
        return $row;
    }

    /**
     * Run the task quietly.
     */
    private function run_task(): void {
        ob_start();
        (new reconcile_pending_assets())->execute();
        ob_end_clean();
    }

    /**
     * A stuck asset that FastPix reports ready becomes ready (with playback id).
     */
    public function test_stuck_asset_reported_ready_becomes_ready(): void {
        global $DB;
        $asset = $this->insert_asset('created', 3600, 'media-ready');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')->willReturn((object)['data' => (object)[
            'status'      => 'Ready',
            'playbackIds' => [(object)['id' => 'pb-rc', 'accessPolicy' => 'public']],
        ]]);
        $this->set_gateway($mock);

        $this->run_task();

        $row = $DB->get_record(self::TABLE, ['id' => $asset->id], 'status, playback_id, access_policy');
        $this->assertSame('ready', $row->status);
        $this->assertSame('pb-rc', $row->playback_id);
        $this->assertSame('public', $row->access_policy);
    }

    /**
     * A stuck asset still processing on FastPix stays non-terminal.
     */
    public function test_still_processing_stays_pending(): void {
        global $DB;
        $asset = $this->insert_asset('created', 3600, 'media-proc');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')->willReturn((object)['data' => (object)['status' => 'Created']]);
        $this->set_gateway($mock);

        $this->run_task();

        $this->assertSame('created', $DB->get_field(self::TABLE, 'status', ['id' => $asset->id]));
    }

    /**
     * An asset FastPix reports gone (404) becomes terminal errored.
     */
    public function test_gone_on_fastpix_becomes_errored(): void {
        global $DB;
        $asset = $this->insert_asset('created', 3600, 'media-gone');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')
            ->willThrowException(new \local_fastpix\exception\gateway_not_found('gone'));
        $this->set_gateway($mock);

        $this->run_task();

        $this->assertSame('errored', $DB->get_field(self::TABLE, 'status', ['id' => $asset->id]));
    }

    /**
     * A recently-updated asset is not polled (below the stuck threshold).
     */
    public function test_fresh_asset_not_polled(): void {
        $this->insert_asset('created', 60, 'media-fresh'); // 60s old < 30m.
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('get_media');
        $this->set_gateway($mock);

        $this->run_task();
    }

    /**
     * Terminal assets (ready/errored) are not polled.
     */
    public function test_terminal_asset_not_polled(): void {
        $this->insert_asset('ready', 3600, 'media-term');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->expects($this->never())->method('get_media');
        $this->set_gateway($mock);

        $this->run_task();
    }

    /**
     * On gateway_unavailable (breaker open / outage) the run stops and the
     * asset is left unchanged (ADR-016 guard).
     */
    public function test_gateway_unavailable_defers_without_change(): void {
        global $DB;
        $asset = $this->insert_asset('created', 3600, 'media-down');
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('get_media')
            ->willThrowException(new \local_fastpix\exception\gateway_unavailable('circuit_open:x'));
        $this->set_gateway($mock);

        $this->run_task();

        $this->assertSame('created', $DB->get_field(self::TABLE, 'status', ['id' => $asset->id]));
    }
}
