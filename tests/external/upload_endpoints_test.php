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
 * Capability/context authorization tests for the upload external functions.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * Asserts that the three upload endpoints authorize mod/fastpix:uploadmedia
 * against the COURSE context supplied by the caller, not context_system.
 *
 * Regression: the capability is declared CONTEXT_COURSE (mod_fastpix, ADR-012),
 * so checking it at the system context denied editing teachers while only site
 * admins (who bypass capability checks) succeeded.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_fastpix\external\get_upload_status
 * @covers     \local_fastpix\external\create_upload_session
 * @covers     \local_fastpix\external\create_url_pull_session
 */
final class upload_endpoints_test extends \advanced_testcase {
    /**
     * Insert an upload_session row owned by the given user.
     *
     * @param int $userid
     * @return int Inserted session row id
     */
    private function seed_session(int $userid): int {
        global $DB;
        return (int)$DB->insert_record('local_fastpix_upload_session', (object)[
            'userid'      => $userid,
            'upload_id'   => 'upl-test-0001',
            'upload_url'  => 'https://storage.example.com/upload',
            'fastpix_id'  => null,
            'source_url'  => null,
            'state'       => 'pending',
            'timecreated' => time(),
            'expires_at'  => time() + 3600,
        ]);
    }

    /**
     * An editing teacher in the course is authorized against the course context.
     */
    public function test_editing_teacher_is_authorised(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $sessionid = $this->seed_session((int)$teacher->id);

        $this->setUser($teacher);
        $result = get_upload_status::execute($context->id, $sessionid);

        $this->assertSame($sessionid, $result['session_id']);
        $this->assertSame('pending', $result['state']);
    }

    /**
     * A student (no :uploadmedia) is denied on the read endpoint after passing
     * validate_context — proving the gate is the capability, at course context.
     */
    public function test_student_is_denied_on_read(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $sessionid = $this->seed_session((int)$student->id);

        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        get_upload_status::execute($context->id, $sessionid);
    }

    /**
     * Same denial holds on a write endpoint (sesskey satisfied so the failure
     * is unambiguously the capability check, not CSRF).
     */
    public function test_student_is_denied_on_write(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\required_capability_exception::class);
        create_upload_session::execute($context->id, 'My video', 'public', 'none', '');
    }

    /**
     * A non-existent context id is rejected before any capability check.
     */
    public function test_unknown_contextid_is_rejected(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $sessionid = $this->seed_session((int)$teacher->id);

        $this->setUser($teacher);
        $this->expectException(\dml_missing_record_exception::class);
        get_upload_status::execute(7654321, $sessionid);
    }

    /**
     * A real context the caller has no access to is rejected by validate_context
     * (teacher enrolled only in course1, queries course2's context).
     */
    public function test_forbidden_context_is_rejected(): void {
        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $context2 = \context_course::instance($course2->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course1, 'editingteacher');
        $sessionid = $this->seed_session((int)$teacher->id);

        $this->setUser($teacher);
        $this->expectException(\require_login_exception::class);
        get_upload_status::execute($context2->id, $sessionid);
    }
}
