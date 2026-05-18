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
 * Service: asset service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

/**
 * Service: asset.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asset_service {
    /** @var string Table. */
    private const TABLE = 'local_fastpix_asset';

    // Public read API.

    /**
     * Get by fastpix id.
     *
     * @param string $fastpixid
     * @param bool $includedeleted
     * @return ?\stdClass
     */
    public static function get_by_fastpix_id(string $fastpixid, bool $includedeleted = false): ?\stdClass {
        $cache = self::cache();
        $key = self::cache_key_fastpix($fastpixid);

        $row = $cache->get($key);
        if ($row === false) {
            global $DB;
            $row = $DB->get_record(self::TABLE, ['fastpix_id' => $fastpixid]);
            if ($row) {
                $cache->set($key, $row);
                if (!empty($row->playback_id)) {
                    $cache->set(self::cache_key_playback($row->playback_id), $row);
                }
            }
        }

        if (!$row) {
            return null;
        }
        if (!$includedeleted && !empty($row->deleted_at)) {
            return null;
        }
        return $row;
    }

    /**
     * Get by playback id.
     *
     * @param string $playbackid
     * @param bool $includedeleted
     * @return ?\stdClass
     */
    public static function get_by_playback_id(string $playbackid, bool $includedeleted = false): ?\stdClass {
        $cache = self::cache();
        $key = self::cache_key_playback($playbackid);

        $row = $cache->get($key);
        if ($row === false) {
            global $DB;
            $row = $DB->get_record(self::TABLE, ['playback_id' => $playbackid]);
            if ($row) {
                $cache->set($key, $row);
                $cache->set(self::cache_key_fastpix($row->fastpix_id), $row);
            }
        }

        if (!$row) {
            return null;
        }
        if (!$includedeleted && !empty($row->deleted_at)) {
            return null;
        }
        return $row;
    }

    /**
     * Get by id.
     *
     * @param int $id
     * @param bool $includedeleted
     * @return ?\stdClass
     */
    public static function get_by_id(int $id, bool $includedeleted = false): ?\stdClass {
        global $DB;
        $row = $DB->get_record(self::TABLE, ['id' => $id]);
        if (!$row) {
            return null;
        }
        if (!$includedeleted && !empty($row->deleted_at)) {
            return null;
        }
        return $row;
    }

    /**
     * Lookup an asset by upload_session id. ADR-013 §2 entry point.
     * Returns null when the session row doesn't exist, has no fastpix_id
     * yet (webhook still in flight), or the linked asset row is
     * soft-deleted. Caching contract piggybacks on get_by_fastpix_id.
     *
     * @param int $sessionid
     * @return ?\stdClass
     */
    public static function get_by_upload_session_id(int $sessionid): ?\stdClass {
        global $DB;
        $session = $DB->get_record(
            'local_fastpix_upload_session',
            ['id' => $sessionid],
            'id, fastpix_id'
        );
        if (!$session || empty($session->fastpix_id)) {
            return null;
        }
        return self::get_by_fastpix_id((string)$session->fastpix_id);
    }

    /**
     * Read-path lazy fetch. May call the gateway exactly once on cold start.
     * Forbidden on write paths (rule W7).
     *
     * @param string $fastpixid
     * @return \stdClass
     */
    public static function get_by_fastpix_id_or_fetch(string $fastpixid): \stdClass {
        $asset = self::get_by_fastpix_id($fastpixid);
        if ($asset !== null) {
            return $asset;
        }

        try {
            $remote = \local_fastpix\api\gateway::instance()->get_media($fastpixid);
        } catch (\local_fastpix\exception\gateway_not_found $e) {
            throw new \local_fastpix\exception\asset_not_found($fastpixid);
        }

        global $DB;

        $data = $remote->data ?? $remote;

        $playbackid = null;
        $accesspolicy = (string)($data->accessPolicy ?? 'private');
        if (!empty($data->playbackIds) && is_array($data->playbackIds)) {
            foreach ($data->playbackIds as $pb) {
                $policy = (string)($pb->accessPolicy ?? '');
                if (in_array($policy, ['private', 'drm'], true)) {
                    $playbackid = (string)$pb->id;
                    $accesspolicy = $policy;
                    break;
                }
            }
        }

        $now = time();
        $row = (object)[
        'fastpix_id'       => (string)$data->id,
        'playback_id'      => $playbackid,
        'owner_userid'     => 0,
        'title'            => (string)($data->title ?? "Imported {$data->id}"),
        'duration'         => $data->duration ?? null,
        'status'           => (string)($data->status ?? 'ready'),
        'access_policy'    => $accesspolicy,
        'drm_required'     => $accesspolicy === 'drm' ? 1 : 0,
        'no_skip_required' => 0,
        'has_captions'     => self::has_caption_track($data) ? 1 : 0,
        'last_event_id'    => null,
        'last_event_at'    => null,
        'deleted_at'       => null,
        'gdpr_delete_pending_at' => null,
        'timecreated'      => $now,
        'timemodified'     => $now,
        ];

        try {
            $row->id = $DB->insert_record(self::TABLE, $row);
        } catch (\dml_write_exception $e) {
            // UNIQUE race — another worker inserted first. Re-read the winner.
            $existing = self::get_by_fastpix_id($fastpixid);
            if ($existing !== null) {
                return $existing;
            }
            throw $e;
        }

        $cache = self::cache();
        $cache->set(self::cache_key_fastpix($row->fastpix_id), $row);
        if (!empty($row->playback_id)) {
            $cache->set(self::cache_key_playback($row->playback_id), $row);
        }

        return $row;
    }

    /**
     * List for owner.
     *
     * @param int $userid
     * @param ?string $status
     * @param int $limit
     * @return array
     */
    public static function list_for_owner(int $userid, ?string $status = 'ready', int $limit = 50): array {
        global $DB;

        $conditions = ['owner_userid' => $userid];
        if ($status !== null) {
            $conditions['status'] = $status;
        }

        $rows = $DB->get_records(
            self::TABLE,
            $conditions,
            'timecreated DESC',
            '*',
            0,
            $limit,
        );

        return array_values(array_filter($rows, static fn($r) => empty($r->deleted_at)));
    }

    /**
     * List for owner paginated.
     *
     * @param int $userid
     * @param ?string $status
     * @param int $offset
     * @param int $limit
     * @param string $search
     * @return array
     */
    public static function list_for_owner_paginated(
        int $userid,
        ?string $status,
        int $offset,
        int $limit,
        string $search = '',
    ): array {
        global $DB;

        $where  = 'owner_userid = :userid AND deleted_at IS NULL';
        $params = ['userid' => $userid];

        if ($status !== null) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $where .= ' AND ' . $DB->sql_like('title', ':search', false);
            $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $rows = $DB->get_records_select(
            self::TABLE,
            $where,
            $params,
            'timecreated DESC',
            '*',
            $offset,
            $limit,
        );

        return array_values($rows);
    }

    // Public write API.

    /**
     * Soft delete.
     *
     * @param int $id
     */
    public static function soft_delete(int $id): void {
        global $DB;

        $row = $DB->get_record(self::TABLE, ['id' => $id], 'id, fastpix_id, playback_id');
        if (!$row) {
            return;
        }

        $now = time();
        $DB->update_record(self::TABLE, (object)[
            'id'           => $id,
            'deleted_at'   => $now,
            'timemodified' => $now,
        ]);

        self::invalidate_cache((string)$row->fastpix_id, $row->playback_id ?? null);
    }

    // Helpers.

    /**
     * Cache.
     *
     * @return \cache_application
     */
    private static function cache(): \cache_application {
        return \cache::make('local_fastpix', 'asset');
    }

    /**
     * MUC area 'asset' is declared simplekeys=true, so cache keys must be
     * alphanumeric + underscore only. We hash the IDs and add a 2-char prefix
     * to keep the fastpix_id and playback_id namespaces disjoint.
     *
     * @param string $fastpixid
     * @return string
     */
    private static function cache_key_fastpix(string $fastpixid): string {
        return \local_fastpix\util\cache_keys::fastpix($fastpixid);
    }

    /**
     * Cache helper for key playback.
     *
     * @param string $playbackid
     * @return string
     */
    private static function cache_key_playback(string $playbackid): string {
        return \local_fastpix\util\cache_keys::playback($playbackid);
    }

    /**
     * Invalidate cache.
     *
     * @param string $fastpixid
     * @param ?string $playbackid
     */
    private static function invalidate_cache(string $fastpixid, ?string $playbackid): void {
        $cache = self::cache();
        $cache->delete(self::cache_key_fastpix($fastpixid));
        if (!empty($playbackid)) {
            $cache->delete(self::cache_key_playback($playbackid));
        }
    }

    /**
     * Whether caption track.
     *
     * @param object $data
     * @return bool
     */
    private static function has_caption_track(object $data): bool {
        if (empty($data->tracks) || !is_array($data->tracks)) {
            return false;
        }
        foreach ($data->tracks as $track) {
            if (($track->type ?? '') === 'subtitle' || ($track->type ?? '') === 'caption') {
                return true;
            }
        }
        return false;
    }
}
