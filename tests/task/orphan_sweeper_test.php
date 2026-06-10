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
 * Tests for the orphan_sweeper scheduled task (stale upload-session cleanup).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Asserts stale, never-progressed upload sessions are deleted past the TTL,
 * while fresh and already-linked sessions are kept.
 *
 * @covers \local_fastpix\task\orphan_sweeper
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class orphan_sweeper_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_upload_session';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \local_fastpix\api\gateway::reset();
        // Stub the gateway so delete_media() never touches the network.
        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $prop = (new \ReflectionClass(\local_fastpix\api\gateway::class))->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $mock);
    }

    public function tearDown(): void {
        parent::tearDown();
        \local_fastpix\api\gateway::reset();
    }

    /**
     * Insert an upload session row with a chosen age and state.
     *
     * @param int $ageseconds How long ago the session was created.
     * @param string $state
     * @return int Inserted row id
     */
    private function insert_session(int $ageseconds, string $state = 'pending'): int {
        global $DB;
        $created = time() - $ageseconds;
        return (int)$DB->insert_record(self::TABLE, (object)[
            'userid'      => 5,
            'upload_id'   => 'upl-' . random_string(8),
            'upload_url'  => 'https://up.example/x',
            'fastpix_id'  => $state === 'pending' ? null : 'fp-' . random_string(8),
            'state'       => $state,
            'timecreated' => $created,
            'expires_at'  => $created + DAYSECS,
        ]);
    }

    /**
     * Default TTL (24h): a 2-day-old pending session is deleted; a fresh one
     * and an already-linked (created) one are kept.
     */
    public function test_stale_pending_deleted_fresh_and_linked_kept(): void {
        global $DB;
        $stale  = $this->insert_session(2 * DAYSECS, 'pending');
        $fresh  = $this->insert_session(60, 'pending');
        $linked = $this->insert_session(2 * DAYSECS, 'created');

        ob_start();
        (new orphan_sweeper())->execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists(self::TABLE, ['id' => $stale]), 'stale pending session must be deleted');
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $fresh]), 'fresh session must be kept');
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $linked]), 'linked session must be kept');
    }

    /**
     * The TTL is driven by the admin setting: with a 1-hour TTL, a 2-hour-old
     * pending session is swept.
     */
    public function test_ttl_honours_admin_setting(): void {
        global $DB;
        set_config('orphaned_session_ttl', HOURSECS, 'local_fastpix');
        $twohours = $this->insert_session(2 * HOURSECS, 'pending');
        $tenmin   = $this->insert_session(10 * MINSECS, 'pending');

        ob_start();
        (new orphan_sweeper())->execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists(self::TABLE, ['id' => $twohours]), 'past custom TTL → deleted');
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $tenmin]), 'within custom TTL → kept');
    }
}
