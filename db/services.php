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
 * External service (web service) definitions for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// The 'description' fields below are English literals, NOT get_string() calls.
// Empirical audit on 2026-05-05 of 23 mod/*/db/services.php files in Moodle 4.5.
// Core shows zero use of get_string() in description — Moodle's web-services UI.
// Does not pass description through the lang loader.

$functions = [
    'local_fastpix_create_upload_session' => [
        'classname'    => '\local_fastpix\external\create_upload_session',
        'methodname'   => 'execute',
        'description'  => 'Create a direct upload session.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'mod/fastpix:uploadmedia',
    ],
    'local_fastpix_create_url_pull_session' => [
        'classname'    => '\local_fastpix\external\create_url_pull_session',
        'methodname'   => 'execute',
        'description'  => 'Create a URL-pull ingest session.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'mod/fastpix:uploadmedia',
    ],
    'local_fastpix_get_upload_status' => [
        'classname'    => '\local_fastpix\external\get_upload_status',
        'methodname'   => 'execute',
        'description'  => 'Poll the status of an upload session.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'mod/fastpix:uploadmedia',
    ],
    'local_fastpix_test_connection' => [
        'classname'    => '\local_fastpix\external\test_connection',
        'methodname'   => 'execute',
        'description'  => 'Probe FastPix reachability from the admin settings page.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/fastpix:configurecredentials',
    ],
    'local_fastpix_send_test_event' => [
        'classname'    => '\local_fastpix\external\send_test_event',
        'methodname'   => 'execute',
        'description'  => 'Fire a synthetic signed webhook event into the local processor for diagnostics.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'local/fastpix:configurecredentials',
    ],
];
