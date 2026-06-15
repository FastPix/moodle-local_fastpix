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
 * Tests for the upload capability guard.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * Verifies the guard degrades gracefully when the capability owner (the FastPix
 * activity module) is absent, while preserving normal allow/deny behaviour when
 * it is present.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_fastpix\external\upload_capability_guard
 */
final class upload_capability_guard_test extends \advanced_testcase {
    /** @var string Capability owned by the FastPix activity module (ADR-012). */
    private const CAP_UPLOAD = 'mod/fastpix:uploadmedia';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Register mod/fastpix:uploadmedia and grant it to editing teachers the way
     * the activity module would. It owns the capability (ADR-012) and is not
     * installed in standalone CI, so the test simulates its presence.
     */
    private function define_upload_capability(): void {
        global $DB;
        if (!$DB->record_exists('capabilities', ['name' => self::CAP_UPLOAD])) {
            $DB->insert_record('capabilities', (object)[
                'name'         => self::CAP_UPLOAD,
                'captype'      => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'component'    => 'mod_fastpix',
                'riskbitmask'  => 0,
            ]);
        }
        $teacherrole = $DB->get_record('role', ['archetype' => 'editingteacher'], '*', IGNORE_MULTIPLE);
        if ($teacherrole) {
            assign_capability(
                self::CAP_UPLOAD,
                CAP_ALLOW,
                $teacherrole->id,
                \context_system::instance()->id,
                true
            );
        }
        accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * When the activity module is absent the capability does not exist; the
     * guard throws a clean, localised module-required error rather than an
     * access-denied or coding exception.
     */
    public function test_missing_module_throws_clean_error(): void {
        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Standalone CI does not install the activity module, so the capability
        // is genuinely absent — this is the production cold-install scenario.
        $this->assertNull(get_capability_info(self::CAP_UPLOAD));

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/activity module/');
        upload_capability_guard::require_upload_capability($context);
    }

    /**
     * With the module present, an authorised editing teacher passes the guard
     * without exception.
     */
    public function test_authorised_user_passes(): void {
        $this->define_upload_capability();
        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        upload_capability_guard::require_upload_capability($context);

        // Reaching this line means no exception was thrown.
        $this->assertDebuggingNotCalled();
    }

    /**
     * With the module present, a user lacking the capability is denied with the
     * standard required_capability_exception — NOT the module-required error.
     */
    public function test_unauthorised_user_denied(): void {
        $this->define_upload_capability();
        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        upload_capability_guard::require_upload_capability($context);
    }
}
