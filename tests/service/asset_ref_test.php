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
 * Tests for asset reference counting (Issue 5).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

/**
 * Asserts add/release/count semantics: a shared asset is only releasable once
 * its last consumer reference is gone.
 *
 * @covers \local_fastpix\service\asset_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class asset_ref_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('local_fastpix', 'asset')->purge();
    }

    /**
     * Insert a ready asset and return its fastpix_id.
     *
     * @param string $fastpixid
     * @return string
     */
    private function insert_asset(string $fastpixid): string {
        global $DB;
        $now = time();
        $DB->insert_record('local_fastpix_asset', (object)[
            'fastpix_id'    => $fastpixid,
            'playback_id'   => null,
            'owner_userid'  => 0,
            'title'         => 'T',
            'status'        => 'ready',
            'access_policy' => 'public',
            'drm_required'  => 0,
            'no_skip_required' => 0,
            'has_captions'  => 0,
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        return $fastpixid;
    }

    /**
     * Two refs, release one → not zero; release the second → zero. Exactly once.
     */
    public function test_two_refs_release_one_keeps_asset_referenced(): void {
        $fpid = $this->insert_asset('fp-ref-1');

        asset_service::add_reference($fpid, 'mod_fastpix:101');
        asset_service::add_reference($fpid, 'mod_fastpix:102');
        $this->assertSame(2, asset_service::reference_count($fpid));

        $this->assertSame(1, asset_service::release_reference($fpid, 'mod_fastpix:101'));
        $this->assertSame(1, asset_service::reference_count($fpid));

        $this->assertSame(0, asset_service::release_reference($fpid, 'mod_fastpix:102'));
        $this->assertSame(0, asset_service::reference_count($fpid));
    }

    /**
     * add_reference is idempotent — the same consumer twice is one reference.
     */
    public function test_add_reference_is_idempotent(): void {
        $fpid = $this->insert_asset('fp-ref-2');

        asset_service::add_reference($fpid, 'mod_fastpix:200');
        asset_service::add_reference($fpid, 'mod_fastpix:200');

        $this->assertSame(1, asset_service::reference_count($fpid));
    }

    /**
     * Releasing a non-existent reference is a no-op and returns the live count.
     */
    public function test_release_unknown_reference_is_noop(): void {
        $fpid = $this->insert_asset('fp-ref-3');
        asset_service::add_reference($fpid, 'mod_fastpix:300');

        $this->assertSame(1, asset_service::release_reference($fpid, 'mod_fastpix:999'));
    }

    /**
     * touch_seen() stamps last_seen_at (the filter render heartbeat) and
     * resolves by either playback id or fastpix id.
     */
    public function test_touch_seen_stamps_last_seen_at(): void {
        global $DB;
        $fpid = $this->insert_asset('fp-seen-1');
        $DB->set_field('local_fastpix_asset', 'playback_id', 'pb-seen-1', ['fastpix_id' => $fpid]);
        \cache::make('local_fastpix', 'asset')->purge();
        \cache::make('local_fastpix', 'seen_heartbeat')->purge();

        $this->assertNull($DB->get_field('local_fastpix_asset', 'last_seen_at', ['fastpix_id' => $fpid]));

        asset_service::touch_seen('pb-seen-1');
        $this->assertNotEmpty(
            $DB->get_field('local_fastpix_asset', 'last_seen_at', ['fastpix_id' => $fpid]),
            'render heartbeat stamps last_seen_at'
        );
    }

    /**
     * touch_seen() is throttled — a second call within the TTL window does not
     * write again.
     */
    public function test_touch_seen_is_throttled(): void {
        global $DB;
        $fpid = $this->insert_asset('fp-seen-2');
        \cache::make('local_fastpix', 'seen_heartbeat')->purge();

        asset_service::touch_seen($fpid);
        $first = (int)$DB->get_field('local_fastpix_asset', 'last_seen_at', ['fastpix_id' => $fpid]);

        // Backdate the stored value; a throttled second call must NOT rewrite it.
        $DB->set_field('local_fastpix_asset', 'last_seen_at', $first - 500, ['fastpix_id' => $fpid]);
        asset_service::touch_seen($fpid);

        $this->assertSame(
            $first - 500,
            (int)$DB->get_field('local_fastpix_asset', 'last_seen_at', ['fastpix_id' => $fpid]),
            'within TTL → no second write'
        );
    }

    /**
     * Issue 4: the asset is soft-deleted only when the LAST reference is
     * released — and exactly once.
     */
    public function test_release_last_reference_soft_deletes_asset(): void {
        global $DB;
        $fpid = $this->insert_asset('fp-rel-1');
        asset_service::add_reference($fpid, 'mod_fastpix:1');
        asset_service::add_reference($fpid, 'mod_fastpix:2');

        asset_service::release_reference($fpid, 'mod_fastpix:1');
        $this->assertNull(
            $DB->get_field('local_fastpix_asset', 'deleted_at', ['fastpix_id' => $fpid]),
            'asset still referenced → must NOT be soft-deleted'
        );

        asset_service::release_reference($fpid, 'mod_fastpix:2');
        $deletedat = $DB->get_field('local_fastpix_asset', 'deleted_at', ['fastpix_id' => $fpid]);
        $this->assertNotEmpty($deletedat, 'last reference gone → asset soft-deleted');

        // Re-release is a harmless no-op; deleted_at is not re-stamped.
        asset_service::release_reference($fpid, 'mod_fastpix:2');
        $this->assertSame(
            (int)$deletedat,
            (int)$DB->get_field('local_fastpix_asset', 'deleted_at', ['fastpix_id' => $fpid]),
            'soft_delete fires at most once'
        );
    }
}
