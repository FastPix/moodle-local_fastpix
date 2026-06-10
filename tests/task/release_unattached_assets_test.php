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
 * Tests for the release_unattached_assets two-phase reaper (Issue 3).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

use local_fastpix\service\asset_service;

/**
 * Asserts the warn-then-release flow: the owner is warned with lead time, the
 * asset is only released after the lead elapses, attaching rescues it, and
 * non-ready / fresh assets are untouched.
 *
 * @covers \local_fastpix\task\release_unattached_assets
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class release_unattached_assets_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_asset';
    /** @var int Test grace window (seconds). */
    private const GRACE = 100;
    /** @var int Test warning lead (seconds). */
    private const LEAD = 40;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('local_fastpix', 'asset')->purge();
        // Small, deterministic windows: warn at (100-40)=60s old, release 40s
        // after the warning.
        set_config('unattached_grace', self::GRACE, 'local_fastpix');
        set_config('unattached_warning_lead', self::LEAD, 'local_fastpix');
    }

    /**
     * Insert an asset row.
     *
     * @param array $overrides
     * @return \stdClass
     */
    private function insert_asset(array $overrides = []): \stdClass {
        global $DB;
        $now = time();
        $row = (object)array_merge([
            'fastpix_id'           => 'media-' . random_string(8),
            'playback_id'          => null,
            'owner_userid'         => 0,
            'title'                => 'Lecture',
            'status'               => 'ready',
            'access_policy'        => 'public',
            'drm_required'         => 0,
            'no_skip_required'     => 0,
            'has_captions'         => 0,
            'deleted_at'           => null,
            'gdpr_delete_attempts' => 0,
            'unattached_warned_at' => null,
            'timecreated'          => $now,
            'timemodified'         => $now,
        ], $overrides);
        $row->id = $DB->insert_record(self::TABLE, $row);
        return $row;
    }

    /**
     * Run the task with message capture; return captured messages.
     *
     * @return array
     */
    private function run_task(): array {
        $sink = $this->redirectMessages();
        ob_start();
        (new release_unattached_assets())->execute();
        ob_end_clean();
        $messages = $sink->get_messages();
        $sink->close();
        return $messages;
    }

    /**
     * Phase 1: an unattached asset past the warn threshold is warned (message +
     * stamp) but NOT released — the owner gets lead time first.
     */
    public function test_warns_with_lead_and_does_not_release(): void {
        global $DB;
        $teacher = $this->getDataGenerator()->create_user();
        $asset = $this->insert_asset([
            'owner_userid' => (int)$teacher->id,
            'timecreated'  => time() - 70, // Past warn threshold (60), within grace.
        ]);

        $messages = $this->run_task();

        $this->assertCount(1, $messages);
        $this->assertEquals((int)$teacher->id, (int)$messages[0]->useridto);
        $row = $DB->get_record(self::TABLE, ['id' => $asset->id], 'unattached_warned_at, deleted_at');
        $this->assertNotEmpty($row->unattached_warned_at, 'warning stamped');
        $this->assertNull($row->deleted_at, 'must NOT be released at warning time');
    }

    /**
     * Phase 2: once the lead window has elapsed since the warning, the asset is
     * soft-deleted.
     */
    public function test_releases_after_lead_elapses(): void {
        global $DB;
        set_config('auto_release_enabled', 1, 'local_fastpix');
        $asset = $this->insert_asset([
            'timecreated'          => time() - 5 * self::GRACE,
            'unattached_warned_at' => time() - (self::LEAD + 10), // Warned > lead ago.
        ]);

        $this->run_task();

        $this->assertNotEmpty(
            $DB->get_field(self::TABLE, 'deleted_at', ['id' => $asset->id]),
            'lead elapsed → soft-deleted'
        );
    }

    /**
     * A warned asset still within its lead window is not released.
     */
    public function test_warned_but_within_lead_not_released(): void {
        global $DB;
        set_config('auto_release_enabled', 1, 'local_fastpix');
        $asset = $this->insert_asset([
            'timecreated'          => time() - 5 * self::GRACE,
            'unattached_warned_at' => time() - 10, // Warned only 10s ago (< lead).
        ]);

        $this->run_task();

        $this->assertNull(
            $DB->get_field(self::TABLE, 'deleted_at', ['id' => $asset->id]),
            'within lead → not released'
        );
    }

    /**
     * An attached asset (has a reference) is never warned or released.
     */
    public function test_attached_asset_is_left_alone(): void {
        global $DB;
        $asset = $this->insert_asset(['timecreated' => time() - 5 * self::GRACE]);
        asset_service::add_reference($asset->fastpix_id, 'mod_fastpix:1');

        $messages = $this->run_task();

        $this->assertCount(0, $messages);
        $row = $DB->get_record(self::TABLE, ['id' => $asset->id], 'unattached_warned_at, deleted_at');
        $this->assertNull($row->unattached_warned_at);
        $this->assertNull($row->deleted_at);
    }

    /**
     * A fresh asset (within the warn threshold) is not warned.
     */
    public function test_fresh_asset_not_warned(): void {
        global $DB;
        $asset = $this->insert_asset(['timecreated' => time() - 10]); // Under the 60s warn threshold.

        $this->run_task();

        $this->assertNull($DB->get_field(self::TABLE, 'unattached_warned_at', ['id' => $asset->id]));
    }

    /**
     * Non-ready assets are not touched.
     */
    public function test_non_ready_asset_is_left_alone(): void {
        global $DB;
        $asset = $this->insert_asset([
            'status'      => 'created',
            'timecreated' => time() - 5 * self::GRACE,
        ]);

        $this->run_task();

        $row = $DB->get_record(self::TABLE, ['id' => $asset->id], 'unattached_warned_at, deleted_at');
        $this->assertNull($row->unattached_warned_at);
        $this->assertNull($row->deleted_at);
    }

    /**
     * Attaching a consumer clears a pending warning (the rescue path).
     */
    public function test_reattach_clears_warning(): void {
        global $DB;
        $asset = $this->insert_asset(['unattached_warned_at' => time() - 5]);

        asset_service::add_reference($asset->fastpix_id, 'mod_fastpix:9');

        $this->assertNull($DB->get_field(self::TABLE, 'unattached_warned_at', ['id' => $asset->id]));
    }

    /**
     * The render heartbeat protects embedded videos: a zero-ref asset rendered
     * recently (filter heartbeat) is neither warned nor released, even with
     * auto-release on. This is the filter/tiny embed safety net.
     */
    public function test_recently_seen_asset_is_protected(): void {
        global $DB;
        set_config('auto_release_enabled', 1, 'local_fastpix');
        $asset = $this->insert_asset([
            'timecreated'  => time() - 5 * self::GRACE,
            'last_seen_at' => time() - 5, // Rendered 5s ago (within grace).
        ]);

        $messages = $this->run_task();

        $this->assertCount(0, $messages, 'recently-rendered video must not be warned');
        $row = $DB->get_record(self::TABLE, ['id' => $asset->id], 'unattached_warned_at, deleted_at');
        $this->assertNull($row->unattached_warned_at);
        $this->assertNull($row->deleted_at, 'embedded/rendered video must never be released');
    }

    /**
     * Default (auto-release OFF): owners are warned but nothing is deleted,
     * even when the asset is well past the lead window.
     */
    public function test_auto_release_disabled_warns_only(): void {
        global $DB;
        // Setting auto_release_enabled defaults to off (not set).
        $asset = $this->insert_asset([
            'timecreated'          => time() - 5 * self::GRACE,
            'unattached_warned_at' => time() - (self::LEAD + 10),
            'owner_userid'         => (int)$this->getDataGenerator()->create_user()->id,
        ]);

        $this->run_task();

        $this->assertNull(
            $DB->get_field(self::TABLE, 'deleted_at', ['id' => $asset->id]),
            'auto-release off → never deleted'
        );
    }
}
