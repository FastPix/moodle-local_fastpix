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
 * Tests for the backfill_asset_owners adhoc task.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Tests the historical backfill of asset.owner_userid from the session ledger.
 *
 * @covers \local_fastpix\task\backfill_asset_owners
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backfill_asset_owners_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Insert an asset row with the given fastpix_id and owner.
     *
     * @param string $fastpixid
     * @param int $owner
     * @return int
     */
    private function insert_asset(string $fastpixid, int $owner): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_fastpix_asset', (object)[
            'fastpix_id'   => $fastpixid,
            'playback_id'  => 'pb-' . substr($fastpixid, -8),
            'owner_userid' => $owner,
            'title'        => 'T',
            'status'       => 'ready',
            'access_policy' => 'public',
            'drm_required' => 0,
            'no_skip_required' => 0,
            'has_captions' => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Insert an upload_session linking a uploader to a fastpix_id.
     *
     * @param int $userid
     * @param string $fastpixid
     */
    private function insert_session(int $userid, string $fastpixid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_fastpix_upload_session', (object)[
            'userid'      => $userid,
            'upload_id'   => $fastpixid,
            'upload_url'  => 'https://example.invalid/u',
            'fastpix_id'  => $fastpixid,
            'state'       => 'created',
            'timecreated' => $now,
            'expires_at'  => $now + 3600,
        ]);
    }

    /**
     * The task recovers sentinel owners from the session ledger and leaves
     * rows with no recoverable owner untouched.
     */
    public function test_backfills_sentinel_owner_from_session(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();

        $recoverable = $this->insert_asset('media-recover', 0);
        $this->insert_session((int)$user->id, 'media-recover');

        // No session → must stay 0.
        $orphan = $this->insert_asset('media-orphan', 0);

        // Already owned → must not change.
        $owned = $this->insert_asset('media-owned', (int)$user->id);

        ob_start();
        (new backfill_asset_owners())->execute();
        ob_end_clean();

        $this->assertEquals((int)$user->id, (int)$DB->get_field('local_fastpix_asset', 'owner_userid', ['id' => $recoverable]));
        $this->assertEquals(0, (int)$DB->get_field('local_fastpix_asset', 'owner_userid', ['id' => $orphan]));
        $this->assertEquals((int)$user->id, (int)$DB->get_field('local_fastpix_asset', 'owner_userid', ['id' => $owned]));
    }

    /**
     * Re-running the task is a no-op once owners are set (idempotent).
     */
    public function test_idempotent_on_rerun(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $id = $this->insert_asset('media-idem', 0);
        $this->insert_session((int)$user->id, 'media-idem');

        ob_start();
        (new backfill_asset_owners())->execute();
        (new backfill_asset_owners())->execute();
        ob_end_clean();

        $this->assertEquals((int)$user->id, (int)$DB->get_field('local_fastpix_asset', 'owner_userid', ['id' => $id]));
    }
}
