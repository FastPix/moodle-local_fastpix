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
 * Event: webhook secret rotated.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\event;

/**
 * Audit event fired when an admin pastes a new webhook signing secret
 * (and a previous value existed). Lets ops trace rotation history via
 * the standard log without exposing any secret material in the event.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook_secret_rotated extends \core\event\base {
    /**
     * Init.
     */    protected function init() {
        $this->data['crud']        = 'u';
        $this->data['edulevel']    = self::LEVEL_OTHER;
        $this->data['objecttable'] = null;
}

    /**
     * Get name.
     */    public static function get_name() {
        return get_string('event_webhook_secret_rotated', 'local_fastpix');
}

    /**
     * Get description.
     */    public function get_description() {
        $when = (int)($this->other['rotated_at'] ?? 0);
        return 'Webhook signing secret rotated at ' . userdate($when);
}
}
