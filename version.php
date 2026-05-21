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
 * Plugin version metadata for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_fastpix';
// Monotonic upgrade version for Moodle's upgrade machinery; it must never
// decrease. The user-visible label is $plugin->release below. A single
// consolidated 1.0.0 release bundles all fixes.
$plugin->version = 2026052200;          // Internal upgrade-version (monotonic).
$plugin->requires  = 2024100100;        // Moodle 4.5 LTS.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';           // Public release label.
