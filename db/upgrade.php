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
        local_fastpix_upgrade_2026050504($dbman);
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
        local_fastpix_upgrade_2026051200($dbman);
        upgrade_plugin_savepoint(true, 2026051200, 'local', 'fastpix');
    }

    // 2026060900: recover owner_userid for assets created with the 0 sentinel.
    // Before this fix the projector never recorded the uploader, so
    // asset_service::list_for_owner() (the tiny_fastpix picker) returned
    // nothing. Backfill the historical backlog from the upload_session ledger
    // via an adhoc task so a large asset table does not block the upgrade.
    if ($oldversion < 2026060900) {
        \core\task\manager::queue_adhoc_task(new \local_fastpix\task\backfill_asset_owners());
        upgrade_plugin_savepoint(true, 2026060900, 'local', 'fastpix');
    }

    // 2026061000: store the chosen upload settings on the session row so the
    // create_upload_session web service can accept title + access policy +
    // captions and apply them to the FastPix upload.
    if ($oldversion < 2026061000) {
        local_fastpix_upgrade_2026061000($dbman);
        upgrade_plugin_savepoint(true, 2026061000, 'local', 'fastpix');
    }

    // 2026061005: reference-tracking table so a shared asset is only released
    // to FastPix when its last consumer unlinks (asset_service ref counting).
    if ($oldversion < 2026061005) {
        local_fastpix_upgrade_2026061005($dbman);
        upgrade_plugin_savepoint(true, 2026061005, 'local', 'fastpix');
    }

    // 2026061006: track when the owner was warned that a ready-but-unattached
    // asset is scheduled for release, so the warning precedes removal by a
    // configurable lead time (release_unattached_assets task, Issue 3).
    if ($oldversion < 2026061006) {
        local_fastpix_upgrade_add_asset_int_field($dbman, 'unattached_warned_at', 'gdpr_delete_attempts');
        upgrade_plugin_savepoint(true, 2026061006, 'local', 'fastpix');
    }

    // 2026061007: usage heartbeat. filter_fastpix stamps last_seen_at whenever
    // it renders an embed, so release_unattached_assets never releases a video
    // that is embedded in live content (assignment, quiz, page, etc.) even
    // though it holds no asset_ref row.
    if ($oldversion < 2026061007) {
        local_fastpix_upgrade_add_asset_int_field($dbman, 'last_seen_at', 'unattached_warned_at');
        upgrade_plugin_savepoint(true, 2026061007, 'local', 'fastpix');
    }

    // 2026061009: make uploads course-aware. courseid is stamped on the upload
    // session so the editor picker can list a teacher's ready, non-DRM videos
    // scoped to the current course.
    if ($oldversion < 2026061009) {
        local_fastpix_upgrade_2026061009($dbman);
        upgrade_plugin_savepoint(true, 2026061009, 'local', 'fastpix');
    }

    // 2026061100: index the upload_session columns the editor picker and the
    // webhook owner-backfill join on (courseid filter, fastpix_id join/OR-lookup)
    // so neither performs a table scan as upload volume grows.
    if ($oldversion < 2026061100) {
        local_fastpix_upgrade_2026061100($dbman);
        upgrade_plugin_savepoint(true, 2026061100, 'local', 'fastpix');
    }

    return true;
}

/**
 * Add an integer column to local_fastpix_asset if it does not already exist.
 *
 * Shared helper for the simple single-column asset upgrades (2026061006, 2026061007).
 *
 * @param database_manager $dbman The database manager.
 * @param string $name The field name to add.
 * @param string $after The existing field this column is placed after.
 */
function local_fastpix_upgrade_add_asset_int_field($dbman, $name, $after) {
    $table = new xmldb_table('local_fastpix_asset');
    $field = new xmldb_field($name, XMLDB_TYPE_INTEGER, '10', null, null, null, null, $after);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}

/**
 * Upgrade step 2026050504: retry-counter column for the GDPR delete cap.
 *
 * @param database_manager $dbman The database manager.
 */
function local_fastpix_upgrade_2026050504($dbman) {
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
}

/**
 * Upgrade step 2026051200: V1.0 production-readiness cleanup (salt, sync_state, config seeds).
 *
 * @param database_manager $dbman The database manager.
 */
function local_fastpix_upgrade_2026051200($dbman) {
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
}

/**
 * Upgrade step 2026061000: store chosen upload settings on the upload_session row.
 *
 * @param database_manager $dbman The database manager.
 */
function local_fastpix_upgrade_2026061000($dbman) {
    $table = new xmldb_table('local_fastpix_upload_session');
    $fields = [
        new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'state'),
        new xmldb_field('access_policy', XMLDB_TYPE_CHAR, '16', null, null, null, null, 'title'),
        new xmldb_field('captions_mode', XMLDB_TYPE_CHAR, '8', null, null, null, null, 'access_policy'),
        new xmldb_field('language_code', XMLDB_TYPE_CHAR, '16', null, null, null, null, 'captions_mode'),
    ];
    foreach ($fields as $field) {
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }
}

/**
 * Upgrade step 2026061005: reference-tracking table for asset ref counting.
 *
 * @param database_manager $dbman The database manager.
 */
function local_fastpix_upgrade_2026061005($dbman) {
    $table = new xmldb_table('local_fastpix_asset_ref');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('asset_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('consumer_key', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('fk_asset', XMLDB_KEY_FOREIGN, ['asset_id'], 'local_fastpix_asset', ['id']);
    $table->add_key('uk_asset_consumer', XMLDB_KEY_UNIQUE, ['asset_id', 'consumer_key']);
    $index = new xmldb_index('idx_asset', XMLDB_INDEX_NOTUNIQUE, ['asset_id']);
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }
}

/**
 * Upgrade step 2026061009: make uploads course-aware (courseid on upload_session).
 *
 * @param database_manager $dbman The database manager.
 */
function local_fastpix_upgrade_2026061009($dbman) {
    $table = new xmldb_table('local_fastpix_upload_session');
    $field = new xmldb_field(
        'courseid',
        XMLDB_TYPE_INTEGER,
        '10',
        null,
        XMLDB_NOTNULL,
        null,
        '0',
        'userid',
    );
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}

/**
 * Upgrade step 2026061100: index upload_session columns used by the picker and backfill join.
 *
 * @param database_manager $dbman The database manager.
 */
function local_fastpix_upgrade_2026061100($dbman) {
    $table = new xmldb_table('local_fastpix_upload_session');
    $indexes = [
        new xmldb_index('idx_fastpix_id', XMLDB_INDEX_NOTUNIQUE, ['fastpix_id']),
        new xmldb_index('idx_courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']),
    ];
    foreach ($indexes as $index) {
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
    }
}
