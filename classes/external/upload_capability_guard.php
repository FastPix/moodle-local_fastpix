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
 * External helper: upload capability guard.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * Guards the upload web-service endpoints behind mod/fastpix:uploadmedia.
 *
 * The capability is owned by the FastPix activity module (ADR-012). This plugin
 * neither defines it (rule M3) nor declares a dependency on the surface plugin
 * (rule A4 — that would invert the fixed dependency direction and create a
 * cycle). When the activity module is absent the capability simply does not
 * exist; a bare require_capability() then surfaces as a confusing
 * "access denied" / coding error to whoever calls the endpoint. This guard
 * converts that single edge case into a clear, localised "module required"
 * message while leaving the normal authorise-or-deny path untouched.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_capability_guard {
    /** @var string Upload capability, owned by the FastPix activity module (ADR-012). */
    private const UPLOAD_CAPABILITY = 'mod/fastpix:uploadmedia';

    /**
     * Require the upload capability, failing cleanly when its owning module is absent.
     *
     * @param \context $context Context to authorize against.
     * @throws \moodle_exception When the capability owner (activity module) is not installed.
     * @throws \required_capability_exception When the user lacks the capability.
     */
    public static function require_upload_capability(\context $context): void {
        // get_capability_info() returns null for an undefined capability without
        // emitting a debugging notice; that is the "owning module not installed"
        // signal. require_capability() below handles the normal allow/deny.
        if (get_capability_info(self::UPLOAD_CAPABILITY) === null) {
            throw new \moodle_exception('upload_module_required', 'local_fastpix');
        }
        require_capability(self::UPLOAD_CAPABILITY, $context);
    }
}
