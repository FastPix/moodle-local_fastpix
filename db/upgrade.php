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
 * Database upgrade steps for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the local_fastpix schema.
 *
 * @param int $oldversion The currently installed version.
 * @return bool true on success.
 */
function xmldb_local_fastpix_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // T3.5 (2026-05-05): retry-counter column for GDPR delete cap.
    if ($oldversion < 2026050504) {
        $table = new xmldb_table('local_fastpix_asset');
        $field = new xmldb_field(
            'gdpr_delete_attempts',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'gdpr_delete_pending_at',
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026050504, 'local', 'fastpix');
    }

    // 2026051200: V1.0 production-readiness cleanup.
    // - Bump user_hash_salt to 64 chars (rule S9). One-time historical.
    // Hash drift; documented in upgrade notes.
    // - Drop local_fastpix_sync_state (reserved for ADR-003 with no ADR;
    // Unused schema removed per the v1.0 review N3 finding).
    // - Seed default_access_policy and max_resolution config rows so.
    // Existing installs pick up the upload-defaults UX.
    // - purge_soft_deleted_assets task auto-registered via db/tasks.php.
    // On upgrade; no schema change required.
    if ($oldversion < 2026051200) {
        $salt = (string)get_config('local_fastpix', 'user_hash_salt');
        if (strlen($salt) < 64) {
            set_config('user_hash_salt', random_string(64), 'local_fastpix');
        }

        $synctable = new xmldb_table('local_fastpix_sync_state');
        if ($dbman->table_exists($synctable)) {
            $dbman->drop_table($synctable);
        }

        if (get_config('local_fastpix', 'default_access_policy') === false) {
            set_config('default_access_policy', 'private', 'local_fastpix');
        }
        if (get_config('local_fastpix', 'max_resolution') === false) {
            set_config('max_resolution', '1080p', 'local_fastpix');
        }

        upgrade_plugin_savepoint(true, 2026051200, 'local', 'fastpix');
    }

    return true;
}
