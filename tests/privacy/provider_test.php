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
 * Tests for the local_fastpix privacy provider.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\external_location;

/**
 * Tests for the local_fastpix privacy provider.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_fastpix\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Insert an upload_session row owned by the given user.
     *
     * @param int $userid
     */
    private function seed_upload_session(int $userid): void {
        global $DB;
        $DB->insert_record('local_fastpix_upload_session', (object)[
            'userid'      => $userid,
            'upload_id'   => 'upl-' . random_string(8),
            'upload_url'  => 'https://storage.example.com/upload',
            'fastpix_id'  => null,
            'source_url'  => null,
            'state'       => 'pending',
            'timecreated' => time(),
            'expires_at'  => time() + 3600,
        ]);
    }

    /**
     * A user with no FastPix data must NOT be reported as having data — the
     * contextlist is empty rather than always claiming the system context.
     */
    public function test_no_data_returns_empty_contextlist(): void {
        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid((int)$user->id);

        $this->assertCount(0, $contextlist);
    }

    /**
     * A user who owns an upload session is reported at the system context.
     */
    public function test_user_with_data_gets_system_context(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->seed_upload_session((int)$user->id);

        $contextlist = provider::get_contexts_for_userid((int)$user->id);

        $this->assertCount(1, $contextlist);
        // The get_contextids() result may include string ids; normalise before comparing.
        $contextids = array_map('intval', $contextlist->get_contextids());
        $this->assertContains((int)\context_system::instance()->id, $contextids);
    }

    /**
     * The external-location disclosure must list the video title and source URL,
     * both of which the gateway transmits to FastPix.
     */
    public function test_metadata_discloses_title_and_source_url(): void {
        $collection = new collection('local_fastpix');
        provider::get_metadata($collection);

        $fields = [];
        foreach ($collection->get_collection() as $item) {
            if ($item instanceof external_location && $item->get_name() === 'fastpix.com') {
                $fields = array_keys($item->get_privacy_fields());
            }
        }

        $this->assertContains('video_title', $fields);
        $this->assertContains('source_url', $fields);
    }
}
