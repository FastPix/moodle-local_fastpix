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
 * Privacy provider for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_fastpix_asset',
            [
                'owner_userid' => 'privacy:metadata:asset:owner_userid',
                'fastpix_id'   => 'privacy:metadata:asset:fastpix_id',
                'title'        => 'privacy:metadata:asset:title',
                'duration'     => 'privacy:metadata:asset:duration',
                'timecreated'  => 'privacy:metadata:asset:timecreated',
            ],
            'privacy:metadata:asset',
        );

        $collection->add_database_table(
            'local_fastpix_upload_session',
            [
                'userid'      => 'privacy:metadata:upload_session:userid',
                'upload_id'   => 'privacy:metadata:upload_session:upload_id',
                'source_url'  => 'privacy:metadata:upload_session:source_url',
                'state'       => 'privacy:metadata:upload_session:state',
                'timecreated' => 'privacy:metadata:upload_session:timecreated',
            ],
            'privacy:metadata:upload_session',
        );

        $collection->add_external_location_link(
            'fastpix.io',
            [
                'moodle_owner_userhash' => 'privacy:metadata:fastpix:owner_userhash',
                'moodle_site_url'       => 'privacy:metadata:fastpix:site_url',
            ],
            'privacy:metadata:fastpix',
        );

        return $collection;
    }

    /**
     * Get contexts for userid.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Get users in context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!($context instanceof \context_system)) {
            return;
        }

        global $DB;
        $assets   = $DB->get_fieldset_select('local_fastpix_asset', 'owner_userid', 'owner_userid > 0');
        $sessions = $DB->get_fieldset_select('local_fastpix_upload_session', 'userid', 'userid > 0');
        $userlist->add_users(array_values(array_unique(array_merge($assets, $sessions))));
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        global $DB;
        $userid  = $contextlist->get_user()->id;
        $context = \context_system::instance();

        $assets = $DB->get_records('local_fastpix_asset', ['owner_userid' => $userid]);
        if (!empty($assets)) {
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_fastpix'), 'assets'],
                (object)['assets' => array_values($assets)],
            );
        }

        $sessions = $DB->get_records('local_fastpix_upload_session', ['userid' => $userid]);
        if (!empty($sessions)) {
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_fastpix'), 'upload_sessions'],
                (object)['sessions' => array_values($sessions)],
            );
        }
    }

    /**
     * Delete data for all users in context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if (!($context instanceof \context_system)) {
            return;
        }

        global $DB;
        $now = time();

        // Mark every still-live asset for GDPR-pending deletion. The.
        // Retention windows declared on this plugin:
        // - Local_fastpix_webhook_event: 90 days (webhook_event_pruner, rule W9).
        // - local_fastpix_asset soft-delete → hard-delete: 7 days.
        // (Purge_soft_deleted_assets, rule W10).
        // - local_fastpix_asset GDPR-pending → hard-delete: 90 days.
        // (Asset_cleanup, GDPR retry path).
        $DB->set_field_select(
            'local_fastpix_asset',
            'gdpr_delete_pending_at',
            $now,
            'gdpr_delete_pending_at IS NULL AND deleted_at IS NULL',
        );
        $DB->set_field_select(
            'local_fastpix_asset',
            'deleted_at',
            $now,
            'deleted_at IS NULL',
        );

        // Upload sessions are transient; remove immediately.
        $DB->delete_records('local_fastpix_upload_session', []);
    }

    /**
     * Delete data for user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        global $DB;
        $userid = $contextlist->get_user()->id;
        $now    = time();

        $DB->set_field_select(
            'local_fastpix_asset',
            'gdpr_delete_pending_at',
            $now,
            'owner_userid = :uid AND gdpr_delete_pending_at IS NULL',
            ['uid' => $userid],
        );
        $DB->set_field_select(
            'local_fastpix_asset',
            'deleted_at',
            $now,
            'owner_userid = :uid AND deleted_at IS NULL',
            ['uid' => $userid],
        );
        $DB->delete_records('local_fastpix_upload_session', ['userid' => $userid]);
    }

    /**
     * Delete data for users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!($context instanceof \context_system)) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        global $DB;
        $now = time();
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->set_field_select(
            'local_fastpix_asset',
            'gdpr_delete_pending_at',
            $now,
            "owner_userid {$insql} AND gdpr_delete_pending_at IS NULL",
            $params,
        );
        $DB->set_field_select(
            'local_fastpix_asset',
            'deleted_at',
            $now,
            "owner_userid {$insql} AND deleted_at IS NULL",
            $params,
        );
        $DB->delete_records_select('local_fastpix_upload_session', "userid {$insql}", $params);
    }
}
