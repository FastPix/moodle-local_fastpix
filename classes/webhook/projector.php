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
 * Webhook component: projector.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\webhook;

use local_fastpix\exception\lock_acquisition_failed;

/**
 * Webhook event projector — applies events onto the asset row under a per-asset lock.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class projector {
    /** @var string Table. */
    private const TABLE = 'local_fastpix_asset';
    /** @var string Lock factory. */
    private const LOCK_FACTORY = 'local_fastpix_projector';
    /** @var int Lock wait seconds. */
    private const LOCK_WAIT_SECONDS = 5;

    /** @var \core\lock\lock_factory $lockfactory */
    private \core\lock\lock_factory $lockfactory;

    /**
     * Constructor.
     *
     * @param ?\core\lock\lock_factory $lockfactory
     */
    public function __construct(?\core\lock\lock_factory $lockfactory = null) {
        $this->lockfactory = $lockfactory
            ?? \core\lock\lock_config::get_lock_factory(self::LOCK_FACTORY);
    }

    /**
     * Project a verified webhook event onto the asset row.
     * Acquires a per-asset lock to serialize concurrent webhooks for the same
     * asset, applies total-ordering with lex tiebreak on event_id, and
     * invalidates the asset cache (both keys) inside the lock.
     *
     * @param \stdClass $event
     */
    public function project(\stdClass $event): void {
        $objecttype = (string)($event->object->type ?? '');
        $fastpixid  = (string)($event->object->id ?? '');

        // Account-level / non-media events are not our concern.
        if ($fastpixid === '' || !$this->is_media_object($objecttype)) {
            return;
        }

        $resource = 'asset_' . $fastpixid;
        $lock     = $this->lockfactory->get_lock($resource, self::LOCK_WAIT_SECONDS);

        if ($lock === false) {
            throw new lock_acquisition_failed('asset_' . $fastpixid);
        }

        try {
            $this->project_inside_lock($event, $fastpixid);
        } finally {
            $lock->release();
        }
    }

    /**
     * Project inside lock.
     *
     * @param \stdClass $event
     * @param string $fastpixid
     */
    private function project_inside_lock(\stdClass $event, string $fastpixid): void {
        global $DB;

        $row = $DB->get_record(self::TABLE, ['fastpix_id' => $fastpixid]);
        $eventtype = (string)($event->type ?? '');

        if ($row === false) {
            // Real FastPix direct uploads never emit `video.media.created`;
            // They go straight from `video.media.upload` (no asset yet) to.
            // `Video.upload.media_created` (asset exists, has playbackIds).
            // To `video.media.ready`. Out-of-order delivery may also drop the.
            // Earlier of those two. Accept any of these as a row-insert trigger.
            // — `video.media.upload` is the only one that does NOT carry the.
            // Asset shape and is skipped (the next event will create the row).
            $inserttriggers = [
            'video.media.created',
            'video.upload.media_created',
            'video.media.ready',
            'video.media.updated',
            ];
            if (in_array($eventtype, $inserttriggers, true)) {
                $row = $this->insert_from_created_event($event, $fastpixid);
            } else {
                debugging(
                    "projector: event {$eventtype} for unknown asset {$fastpixid}",
                    DEBUG_DEVELOPER,
                );
                return;
            }
        }

        if ($this->is_out_of_order($event, $row)) {
            return;
        }

        if (!$this->handle_event($event, $row)) {
            return;
        }

        $row->last_event_id = (string)$event->id;
        $row->last_event_at = $this->event_timestamp($event);
        $row->timemodified  = time();
        $DB->update_record(self::TABLE, $row);

        // Cache invalidation MUST happen inside the lock (rule W5) so a.
        // Concurrent reader cannot repopulate stale data before this writer.
        // Releases.
        $this->invalidate_cache((string)$row->fastpix_id, $row->playback_id ?? null);

        // After projecting media events, link any matching upload_session.
        // Row by upload_id == fastpix_id (URL pulls + direct uploads).
        $this->link_upload_session((string)$row->fastpix_id);
    }

    /**
     * Whether media object.
     *
     * @param string $objecttype
     * @return bool
     */
    private function is_media_object(string $objecttype): bool {
        return in_array($objecttype, ['video.media', 'media'], true);
    }

    /**
     * Resolve the event's wall-clock timestamp.
     * Synthetic test fixtures use `occurredAt` (epoch int).
     * Real FastPix deliveries use `createdAt` (ISO 8601 with nanoseconds,
     * e.g. "2026-05-11T20:42:16.361817248Z"). PHP's strtotime() rejects
     * nanosecond precision on some builds and returns false; we strip
     * the fractional component before parsing to be safe.
     * Returns 0 only when no parseable timestamp is present.
     *
     * @param \stdClass $event
     * @return int
     */
    private function event_timestamp(\stdClass $event): int {
        if (isset($event->occurredAt) && is_numeric($event->occurredAt)) {
            return (int)$event->occurredAt;
        }
        if (isset($event->createdAt)) {
            $iso = preg_replace('/\.\d+/', '', (string)$event->createdAt);
            $ts = strtotime($iso);
            return $ts !== false ? $ts : 0;
        }
        return 0;
    }

    /**
     * Total ordering with lex tiebreak on event_id.
     *
     * @param \stdClass $event
     * @param \stdClass $row
     * @return bool
     */
    private function is_out_of_order(\stdClass $event, \stdClass $row): bool {
        if ($row->last_event_at === null) {
            return false;
        }

        $eventat = $this->event_timestamp($event);
        $lastat  = (int)$row->last_event_at;

        if ($eventat < $lastat) {
            return true;
        }
        if ($eventat > $lastat) {
            return false;
        }
        // Equal timestamps — tiebreak by event_id; smaller-or-equal IDs lose.
        return strcmp((string)$event->id, (string)$row->last_event_id) <= 0;
    }

    /**
     * Apply the event's data onto $row. Returns true if applied, false if the
     * event type was unhandled (caller still records last_event_* on truthy).
     *
     * @param \stdClass $event
     * @param \stdClass $row
     * @return bool
     */
    private function handle_event(\stdClass $event, \stdClass $row): bool {
        $type = (string)($event->type ?? '');
        $data = $event->data ?? new \stdClass();

        switch ($type) {
            case 'video.media.created':
                // Real FastPix `video.media.created` carries data.playbackIds.
                // (observed 2026-05-08). Insert path also reads them, but if.
                // The row was inserted from an earlier `.upload` event the.
                // Playback id needs to be applied here.
                $this->apply_first_playback_id($data, $row);
                return true;

            case 'video.media.upload':
                if (isset($data->status)) {
                    $row->status = (string)$data->status;
                }
                return true;

            case 'video.upload.media_created':
                $this->apply_first_playback_id($data, $row);
                if ($row->status === 'waiting' || $row->status === '') {
                    $row->status = 'created';
                }
                return true;

            case 'video.media.ready':
                $row->status = 'ready';
                $this->apply_first_playback_id($data, $row);
                $duration = $this->parse_duration($data->duration ?? null);
                if ($duration !== null) {
                    $row->duration = $duration;
                }
                $row->has_captions = $this->count_caption_tracks($data) > 0 ? 1 : 0;
                return true;

            case 'video.media.updated':
                if (isset($data->status)) {
                    $row->status = (string)$data->status;
                }
                $duration = $this->parse_duration($data->duration ?? null);
                if ($duration !== null) {
                    $row->duration = $duration;
                }
                return true;

            case 'video.media.failed':
                $row->status = 'errored';
                return true;

            case 'video.media.deleted':
                $row->deleted_at = time();
                return true;

            default:
                // Unhandled type — let event_dispatcher (Phase 4) take over.
                debugging("projector: no handler for {$type}", DEBUG_DEVELOPER);
                return false;
        }
    }

    /**
     * Insert from created event.
     *
     * @param \stdClass $event
     * @param string $fastpixid
     * @return \stdClass
     */
    private function insert_from_created_event(\stdClass $event, string $fastpixid): \stdClass {
        global $DB;

        $data = $event->data ?? new \stdClass();
        $now = time();

        $row = (object)[
            'fastpix_id'             => $fastpixid,
            'playback_id'            => null,
            'owner_userid'           => 0, // Sentinel.
            'title'                  => (string)($data->title ?? "Asset {$fastpixid}"),
            'duration'               => $this->parse_duration($data->duration ?? null),
            'status'                 => (string)($data->status ?? 'created'),
            'access_policy'          => (string)($data->accessPolicy ?? 'private'),
            'drm_required'           => 0,
            'no_skip_required'       => 0,
            'has_captions'           => 0,
            'last_event_id'          => null,
            'last_event_at'          => null,
            'deleted_at'             => null,
            'gdpr_delete_pending_at' => null,
            'timecreated'            => $now,
            'timemodified'           => $now,
        ];
        $this->apply_first_playback_id($data, $row);
        $row->id = $DB->insert_record(self::TABLE, $row);
        return $row;
    }

    /**
     * Extract the first entry from data.playbackIds and write it onto $row.
     * Accepts public/private/drm. No-op when the event carries no
     * playbackIds. FastPix's real video.media.created and video.media.ready
     * payloads carry playbackIds with accessPolicy "public" by default
     * (observed 2026-05-08).
     *
     * @param \stdClass $data
     * @param \stdClass $row
     */
    private function apply_first_playback_id(\stdClass $data, \stdClass $row): void {
        if (empty($data->playbackIds) || !is_array($data->playbackIds)) {
            return;
        }
        $pb = (object)$data->playbackIds[0];
        $id = (string)($pb->id ?? '');
        if ($id === '') {
            return;
        }
        $row->playback_id = $id;
        $policy = (string)($pb->accessPolicy ?? '');
        if (in_array($policy, ['public', 'private', 'drm'], true)) {
            $row->access_policy = $policy;
            $row->drm_required  = $policy === 'drm' ? 1 : 0;
        }
    }

    /**
     * FastPix sends duration as either a numeric (legacy test fixtures) or
     * "HH:MM:SS[.fff]" (real direct-upload deliveries). The DB column is
     * numeric(10,3); a raw "HH:MM:SS" write throws dml_write_exception.
     * Returns null when value is missing/unparseable so caller leaves the
     * existing column value alone.
     *
     * @param mixed $value
     * @return ?float
     */
    private function parse_duration($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value) && preg_match('/^(\d+):(\d+):(\d+(?:\.\d+)?)$/', $value, $m)) {
            return ((int)$m[1]) * 3600 + ((int)$m[2]) * 60 + (float)$m[3];
        }
        return null;
    }

    /**
     * FastPix uses one UUID for both upload-session and media on URL pulls.
     * Link any matching upload_session row so consumers can navigate
     * session_id → fastpix_id → asset. Idempotent — only touches rows
     * whose fastpix_id is still null.
     *
     * @param string $fastpixid
     */
    private function link_upload_session(string $fastpixid): void {
        global $DB;
        $DB->execute(
            "UPDATE {local_fastpix_upload_session}
                SET fastpix_id = :fpid, state = 'created'
              WHERE upload_id = :upid AND fastpix_id IS NULL",
            ['fpid' => $fastpixid, 'upid' => $fastpixid]
        );
    }

    /**
     * Count caption tracks.
     *
     * @param \stdClass $data
     * @return int
     */
    private function count_caption_tracks(\stdClass $data): int {
        if (empty($data->tracks) || !is_array($data->tracks)) {
            return 0;
        }
        $count = 0;
        foreach ($data->tracks as $track) {
            $kind = (string)($track->type ?? '');
            if ($kind === 'subtitle' || $kind === 'caption') {
                $count++;
            }
        }
        return $count;
    }

    // Cache invalidation (mirrors asset_service helpers).

    /**
     * Invalidate cache.
     *
     * @param string $fastpixid
     * @param ?string $playbackid
     */
    private function invalidate_cache(string $fastpixid, ?string $playbackid): void {
        $cache = \cache::make('local_fastpix', 'asset');
        $cache->delete(\local_fastpix\util\cache_keys::fastpix($fastpixid));
        if (!empty($playbackid)) {
            $cache->delete(\local_fastpix\util\cache_keys::playback($playbackid));
        }
    }

    /**
     * Reflection seam for tests that verify the projector targets the same
     * keys as asset_service. Formula lives in
     * \local_fastpix\util\cache_keys.
     *
     * @param string $fastpixid
     * @return string
     */
    private function cache_key_fastpix(string $fastpixid): string {
        return \local_fastpix\util\cache_keys::fastpix($fastpixid);
    }

    /**
     * Cache helper for key playback.
     *
     * @param string $playbackid
     * @return string
     */
    private function cache_key_playback(string $playbackid): string {
        return \local_fastpix\util\cache_keys::playback($playbackid);
    }
}
