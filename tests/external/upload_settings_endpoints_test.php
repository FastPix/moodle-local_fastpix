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
 * External-layer tests for the settings-based upload + add-subtitle endpoints.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * Exercises the create_upload_session + add_subtitle_track web services through
 * the full external entry point (auth + delegation), with the FastPix gateway
 * mocked so no HTTP leaves the box.
 *
 * @covers     \local_fastpix\external\create_upload_session
 * @covers     \local_fastpix\external\add_subtitle_track
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upload_settings_endpoints_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \local_fastpix\service\upload_service::reset();
        \local_fastpix\service\feature_flag_service::reset();
        \local_fastpix\api\gateway::reset();
        \cache::make('local_fastpix', 'upload_dedup')->purge();
        \cache::make('local_fastpix', 'asset')->purge();
        set_config('user_hash_salt', 'fixed-salt-for-test', 'local_fastpix');
    }

    public function tearDown(): void {
        parent::tearDown();
        \local_fastpix\service\upload_service::reset();
        \local_fastpix\api\gateway::reset();
    }

    /**
     * Inject a gateway mock as the singleton.
     *
     * @param mixed $mock
     */
    private function inject_gateway_mock($mock): void {
        $prop = (new \ReflectionClass(\local_fastpix\api\gateway::class))->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $mock);
    }

    /**
     * Happy path: an editing teacher gets a signed upload URL back.
     */
    public function test_create_upload_session_returns_upload_url(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('input_video_direct_upload')->willReturn((object)['data' => (object)[
            'uploadId' => 'u-ext-1',
            'url'      => 'https://up.fastpix.com/u-ext-1',
        ]]);
        $this->inject_gateway_mock($mock);

        $this->setUser($teacher);
        $_POST['sesskey'] = sesskey();

        $result = create_upload_session::execute($context->id, 'Lecture 1', 'public', 'none', '');

        // session_id is the integer the consumer stores (ADR-015) — without it,
        // PARAM_INT truncates the UUID and direct-upload playback never resolves.
        $this->assertIsInt($result['session_id']);
        $this->assertGreaterThan(0, $result['session_id']);
        $this->assertTrue(
            $DB->record_exists('local_fastpix_upload_session',
                ['id' => $result['session_id'], 'upload_id' => 'u-ext-1', 'userid' => (int)$teacher->id]),
            'create_upload_session must persist a session row owned by the uploader'
        );
        // The course context the caller passed is resolved and stamped on the row.
        $this->assertEquals(
            (int)$course->id,
            (int)$DB->get_field('local_fastpix_upload_session', 'courseid', ['id' => $result['session_id']]),
            'courseid is derived from the course context and persisted'
        );
        $this->assertSame('https://up.fastpix.com/u-ext-1', $result['uploadurl']);
        $this->assertSame('u-ext-1', $result['uploadid']);
    }

    /**
     * add_subtitle_track returns a track id for media the caller owns.
     */
    public function test_add_subtitle_track_returns_track_id_for_owner(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $now = time();
        $DB->insert_record('local_fastpix_asset', (object)[
            'fastpix_id'    => 'm-ext-1',
            'playback_id'   => null,
            'owner_userid'  => (int)$teacher->id,
            'title'         => 'T',
            'status'        => 'ready',
            'access_policy' => 'public',
            'drm_required'  => 0,
            'no_skip_required' => 0,
            'has_captions'  => 0,
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        $mock = $this->createMock(\local_fastpix\api\gateway::class);
        $mock->method('add_media_track')->willReturn((object)['data' => (object)['id' => 'trk-ext-1']]);
        $this->inject_gateway_mock($mock);

        $this->setUser($teacher);
        $_POST['sesskey'] = sesskey();

        $result = add_subtitle_track::execute($context->id, 'm-ext-1', 'en', 'https://1.2.3.4/s.vtt');
        $this->assertSame('trk-ext-1', $result['trackid']);
    }

    /**
     * A student (no :uploadmedia) is denied on add_subtitle_track.
     */
    public function test_add_subtitle_track_denies_student(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\required_capability_exception::class);
        add_subtitle_track::execute($context->id, 'm-ext-x', 'en', 'https://cdn.example.com/s.vtt');
    }
}
