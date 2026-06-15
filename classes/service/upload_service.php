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
 * Service: upload service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

use local_fastpix\exception\drm_not_configured;
use local_fastpix\exception\ssrf_blocked;

/**
 * Service: upload.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_service {
    /** @var string Table. */
    private const TABLE = 'local_fastpix_upload_session';
    /** @var int Session ttl seconds. */
    private const SESSION_TTL_SECONDS = 86400;

    /** @var string MUC area for upload dedup. */
    private const DEDUP_AREA = 'upload_dedup';

    /** @var string Access policy: public. */
    private const POLICY_PUBLIC = 'public';
    /** @var string Access policy: private. */
    private const POLICY_PRIVATE = 'private';
    /** @var string Access policy: DRM-protected. */
    private const POLICY_DRM = 'drm';
    /** @var string[] Allowed access policies. */
    private const ACCESS_POLICIES = [self::POLICY_PUBLIC, self::POLICY_PRIVATE, self::POLICY_DRM];

    /** @var string SSRF block tag (IPv4 / generic). */
    private const SSRF_TAG_IP = 'blocked_ip:';
    /** @var string SSRF block tag (IPv6). */
    private const SSRF_TAG_IPV6 = 'blocked_ipv6:';

    /**
     * Auto-subtitle (Whisper) languages: code => display name. The spoken
     * language is detected/transcribed, not translated. Supported tier first,
     * beta tier after. Verified against the FastPix auto-subtitles docs.
     *
     * @var array<string,string>
     */
    private const SUBTITLE_LANGUAGES = [
        // Supported.
        'en' => 'English', 'es' => 'Spanish', 'it' => 'Italian',
        'pt' => 'Portuguese', 'de' => 'German', 'fr' => 'French',
        // Beta.
        'pl' => 'Polish', 'ru' => 'Russian', 'nl' => 'Dutch', 'ca' => 'Catalan',
        'tr' => 'Turkish', 'sv' => 'Swedish', 'uk' => 'Ukrainian', 'no' => 'Norwegian',
        'fi' => 'Finnish', 'sk' => 'Slovak', 'el' => 'Greek', 'cs' => 'Czech',
        'hr' => 'Croatian', 'da' => 'Danish', 'ro' => 'Romanian', 'bg' => 'Bulgarian',
    ];

    /** @var ?self $instance */
    private static ?self $instance = null;

    /**
     * Singleton accessor.
     *
     * @return self
     */
    public static function instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Reset the singleton (used by tests).
     */    public static function reset(): void {
        self::$instance = null;
}

    /**
     * Create file upload session.
     *
     * @param int $userid
     * @param array $metadata
     * @param bool $drmrequired
     * @param ?string $accesspolicy
     * @param ?string $maxresolution
     * @param int $courseid
     * @return \stdClass
     */
public function create_file_upload_session(
    int $userid,
    array $metadata,
    bool $drmrequired = false,
    ?string $accesspolicy = null,
    ?string $maxresolution = null,
    int $courseid = 0,
): \stdClass {
    // Gate on the EFFECTIVE access policy, not the raw $drmrequired flag: the
    // policy can resolve to self::POLICY_DRM via the caller's value or the admin
    // default_access_policy config without $drmrequired being set. Either way,
    // a self::POLICY_DRM upload requires the W12 double-gate (drm_enabled()).
    $this->assert_drm_gate($this->resolve_access_policy($drmrequired, $accesspolicy));

    // Dedup window: same (userid, filename, size) within 60s returns the.
    // Existing session.
    $cache = \cache::make('local_fastpix', self::DEDUP_AREA);
    $hashkey = $this->dedup_key($userid, $metadata);
    $cached = $this->dedup_hit($cache, $hashkey);
    if ($cached !== null) {
        return $cached;
    }

    $params = $this->resolve_upload_params($userid, $drmrequired, $accesspolicy, $maxresolution);

    $response = \local_fastpix\api\gateway::instance()->input_video_direct_upload(
        $params['owner_hash'],
        $params['fastpix_metadata'],
        $params['access_policy'],
        $params['drm_config_id'],
        $params['max_resolution'],
    );

    $uploadid = (string)($response->data->uploadId ?? $response->uploadId ?? '');
    $uploadurl = (string)($response->data->url ?? $response->url ?? '');

    $session = $this->persist_session(
        userid:     $userid,
        uploadid:  $uploadid,
        uploadurl: $uploadurl,
        sourceurl: null,
        courseid:  $courseid,
    );

    $cache->set($hashkey, $session->id);

    return $this->build_response($session, deduped: false);
}

    /**
     * Create url pull session.
     *
     * @param int $userid
     * @param string $sourceurl
     * @param bool $drmrequired
     * @param ?string $accesspolicy
     * @param ?string $maxresolution
     * @param int $courseid
     * @return \stdClass
     */
public function create_url_pull_session(
    int $userid,
    string $sourceurl,
    bool $drmrequired = false,
    ?string $accesspolicy = null,
    ?string $maxresolution = null,
    int $courseid = 0,
): \stdClass {
    // SSRF guard runs BEFORE any gateway call (rule S6).
    $this->assert_ssrf_safe($sourceurl);
    // Gate on the EFFECTIVE access policy, not the raw $drmrequired flag: the
    // policy can resolve to self::POLICY_DRM via the caller's value or the admin
    // default_access_policy config without $drmrequired being set. Either way,
    // a self::POLICY_DRM upload requires the W12 double-gate (drm_enabled()).
    $this->assert_drm_gate($this->resolve_access_policy($drmrequired, $accesspolicy));

    // Dedup window: same (userid, source_url) within 60s returns the.
    // Existing session row. Mirrors the file-upload dedup contract (W11).
    $cache = \cache::make('local_fastpix', self::DEDUP_AREA);
    $hashkey = $this->dedup_key_url($userid, $sourceurl);
    $cached = $this->dedup_hit($cache, $hashkey);
    if ($cached !== null) {
        return $cached;
    }

    $params = $this->resolve_upload_params($userid, $drmrequired, $accesspolicy, $maxresolution);

    $response = \local_fastpix\api\gateway::instance()->media_create_from_url(
        $sourceurl,
        $params['owner_hash'],
        $params['fastpix_metadata'],
        $params['access_policy'],
        $params['drm_config_id'],
        $params['max_resolution'],
    );

    $uploadid = (string)($response->data->id ?? $response->id ?? '');

    $session = $this->persist_session(
        userid:     $userid,
        uploadid:  $uploadid,
        uploadurl: '',
        sourceurl: $sourceurl,
        courseid:  $courseid,
    );

    $cache->set($hashkey, $session->id);

    return $this->build_response($session, deduped: false);
}

    /**
     * Create a direct upload, applying the uploader's chosen settings
     * (title + access policy + captions) to the FastPix pushMediaSettings.
     *
     * @param int $userid
     * @param string $title
     * @param string $accesspolicy "public" | "private" | "drm"
     * @param string $captionsmode "none" | "auto" | "vtt"
     * @param ?string $languagecode Required (and validated) when captionsmode = "auto".
     * @param int $courseid
     * @return \stdClass {session_id, upload_id, upload_url, expires_at}
     * @throws \invalid_parameter_exception on bad policy/captions/language.
     * @throws drm_not_configured when policy is self::POLICY_DRM but DRM is not configured.
     */
public function create_direct_upload_with_settings(
    int $userid,
    string $title,
    string $accesspolicy,
    string $captionsmode,
    ?string $languagecode = null,
    int $courseid = 0,
): \stdClass {
    if (!in_array($accesspolicy, self::ACCESS_POLICIES, true)) {
        throw new \invalid_parameter_exception('accesspolicy:' . $accesspolicy);
    }
    if (!in_array($captionsmode, ['none', 'auto', 'vtt'], true)) {
        throw new \invalid_parameter_exception('captionsmode:' . $captionsmode);
    }

    // W12 double-gate: self::POLICY_DRM intent requires drm_enabled(); fail loud — never
    // silently downgrade a DRM request to an unprotected upload.
    $this->assert_drm_gate($accesspolicy);

    // Auto-captions: validate the spoken language and build the subtitles
    // object. FastPix wants a single {languageName, languageCode} object — a
    // list is rejected with HTTP 400 (verified live 2026-06-09).
    $subtitles = null;
    if ($captionsmode === 'auto') {
        $languagename = $this->subtitle_language_name($languagecode);
        $subtitles = [
            'languageName' => $languagename,
            'languageCode' => (string)$languagecode,
        ];
    }

    // DRM uploads send accessPolicy=self::POLICY_DRM alongside the drmConfigurationId.
    // FastPix REQUIRES this pairing — accessPolicy='private'+drmConfigurationId
    // is rejected with HTTP 400 ("drmConfigurationId is only applicable when
    // accessPolicy is set to self::POLICY_DRM"), verified live 2026-06-09.
    $fastpixpolicy = $accesspolicy;
    $drmconfigid  = $accesspolicy === self::POLICY_DRM
        ? feature_flag_service::instance()->drm_configuration_id()
        : null;

    // Double-submit guard (W11 spirit): identical settings from one user inside
    // the 60s window return the same session rather than a duplicate upload.
    $cache   = \cache::make('local_fastpix', self::DEDUP_AREA);
    $hashkey = $this->dedup_key_settings($userid, $title, $accesspolicy, $captionsmode, $languagecode);
    $cached  = $this->dedup_hit($cache, $hashkey);
    if ($cached !== null) {
        return $cached;
    }

    $ownerhash = $this->owner_hash($userid);
    $response  = \local_fastpix\api\gateway::instance()->input_video_direct_upload(
        $ownerhash,
        [
            'moodle_owner_userhash' => $ownerhash,
            'moodle_site_url'       => (new \moodle_url('/'))->out(false),
        ],
        $fastpixpolicy,
        $drmconfigid,
        $this->resolve_max_resolution(null),
        $subtitles,
        // The pushMediaSettings.title — FastPix's media-title field; surfaces
        // at the asset's data.title, which the projector reads to name the asset.
        $title,
    );

    $uploadid  = (string)($response->data->uploadId ?? $response->uploadId ?? '');
    $uploadurl = (string)($response->data->url ?? $response->url ?? '');

    $session = $this->persist_session_with_settings(
        $userid,
        $uploadid,
        $uploadurl,
        [
            'title'         => $title,
            'access_policy' => $accesspolicy,
            'captions_mode' => $captionsmode,
            'language_code' => $languagecode,
        ],
        $courseid,
    );
    $cache->set($hashkey, $session->id);

    return $this->build_response($session, deduped: false);
}

    /**
     * Attach a manual subtitle (.vtt) track to a ready asset via FastPix's
     * Add Track API. Owner-scoped: a caller can only add tracks to media they
     * own (mirrors get_status — not-owned is indistinguishable from not-found).
     *
     * @param int $userid
     * @param string $mediaid FastPix media id (asset fastpix_id).
     * @param string $languagecode
     * @param string $vtturl Public HTTPS URL FastPix will fetch the .vtt from.
     * @return \stdClass {track_id}
     * @throws \invalid_parameter_exception on unsupported language.
     * @throws \local_fastpix\exception\asset_not_found when not owned / unknown.
     * @throws ssrf_blocked when $vtturl fails the SSRF allow-list.
     */
public function add_subtitle_track(
    int $userid,
    string $mediaid,
    string $languagecode,
    string $vtturl,
): \stdClass {
    $languagename = $this->subtitle_language_name($languagecode);

    // Owner-scope BEFORE any network call. get_by_fastpix_id is a read-path
    // cache lookup (no lazy fetch — rule W7 not engaged here).
    $asset = \local_fastpix\service\asset_service::get_by_fastpix_id($mediaid);
    if ($asset === null || (int)$asset->owner_userid !== $userid) {
        throw new \local_fastpix\exception\asset_not_found('media:' . $mediaid);
    }

    // SSRF allow-list runs BEFORE the gateway call (rule S6).
    $this->assert_ssrf_safe($vtturl);

    $response = \local_fastpix\api\gateway::instance()->add_media_track(
        $mediaid,
        $vtturl,
        $languagecode,
        $languagename,
    );

    return (object)[
        'track_id' => (string)($response->data->id ?? $response->id ?? ''),
    ];
}

    /**
     * Resolve a supported auto-subtitle language code to its display name.
     *
     * @param ?string $code
     * @return string
     * @throws \invalid_parameter_exception when the code is missing/unsupported.
     */
private function subtitle_language_name(?string $code): string {
    $code = (string)$code;
    if (!isset(self::SUBTITLE_LANGUAGES[$code])) {
        throw new \invalid_parameter_exception('unsupported_subtitle_language:' . $code);
    }
    return self::SUBTITLE_LANGUAGES[$code];
}

    /**
     * Dedup key for the settings-based direct upload entry point. Same user +
     * identical (title, policy, captions, language) inside the 60s window
     * returns the existing session (double-submit guard).
     *
     * @param int $userid
     * @param string $title
     * @param string $policy
     * @param string $captions
     * @param ?string $lang
     * @return string
     */
private function dedup_key_settings(
    int $userid,
    string $title,
    string $policy,
    string $captions,
    ?string $lang,
): string {
    $logical = "upset:{$userid}:" . hash('sha256', implode('|', [$title, $policy, $captions, (string)$lang]));
    return 'us_' . substr(hash('sha256', $logical), 0, 32);
}

    /**
     * Persist an upload session row carrying the chosen settings.
     *
     * @param int $userid
     * @param string $uploadid
     * @param string $uploadurl
     * @param array $settings {title:string, access_policy:string, captions_mode:string, language_code:?string}
     * @param int $courseid
     * @return \stdClass
     */
private function persist_session_with_settings(
    int $userid,
    string $uploadid,
    string $uploadurl,
    array $settings,
    int $courseid = 0,
): \stdClass {
    global $DB;
    $now = time();
    $languagecode = $settings['language_code'] ?? null;
    $row = (object)[
        'userid'        => $userid,
        'courseid'      => $courseid,
        'upload_id'     => $uploadid,
        'upload_url'    => $uploadurl,
        'fastpix_id'    => null,
        'source_url'    => null,
        'state'         => 'pending',
        'title'         => $settings['title'],
        'access_policy' => $settings['access_policy'],
        'captions_mode' => $settings['captions_mode'],
        'language_code' => ($languagecode !== null && $languagecode !== '') ? $languagecode : null,
        'timecreated'   => $now,
        'expires_at'    => $now + self::SESSION_TTL_SECONDS,
    ];
    $row->id = $DB->insert_record(self::TABLE, $row);
    return $row;
}

    /**
     * Common dedup-cache short-circuit shared by both session-creation paths.
     * Returns the cached session response if a non-expired row exists for the
     * supplied hash key; null otherwise. Caller still owns the cache->set on
     * the new session id after a fresh insert.
     *
     * @param \cache $cache
     * @param string $hashkey
     * @return ?\stdClass
     */
private function dedup_hit(\cache $cache, string $hashkey): ?\stdClass {
    $existingid = $cache->get($hashkey);
    if (is_int($existingid) || (is_string($existingid) && ctype_digit($existingid))) {
        $existing = $this->lookup_session((int)$existingid);
        if ($existing !== null && $existing->expires_at > time()) {
            return $this->build_response($existing, deduped: true);
        }
    }
    return null;
}

    /**
     * Resolve the parameters used by both file-upload and URL-pull session
     * creation paths: owner hash, effective access policy, effective max
     * resolution, DRM config id (only populated when policy=self::POLICY_DRM), and the
     * fastpix_metadata bag attached to the gateway call.
     *
     * @return array{owner_hash:string,access_policy:string,max_resolution:string,drm_config_id:?string,fastpix_metadata:array<string,string>}
     * @param int $userid
     * @param bool $drmrequired
     * @param ?string $accesspolicy
     * @param ?string $maxresolution
     * @return array
     */
private function resolve_upload_params(
    int $userid,
    bool $drmrequired,
    ?string $accesspolicy,
    ?string $maxresolution,
): array {
    $ownerhash     = $this->owner_hash($userid);
    $accesspolicy  = $this->resolve_access_policy($drmrequired, $accesspolicy);
    $maxresolution = $this->resolve_max_resolution($maxresolution);
    $drmconfigid  = $accesspolicy === self::POLICY_DRM
        ? feature_flag_service::instance()->drm_configuration_id()
        : null;
    return [
        'owner_hash'       => $ownerhash,
        'access_policy'    => $accesspolicy,
        'max_resolution'   => $maxresolution,
        'drm_config_id'    => $drmconfigid,
        'fastpix_metadata' => [
            'moodle_owner_userhash' => $ownerhash,
            'moodle_site_url'       => (new \moodle_url('/'))->out(false),
        ],
    ];
}

    // Helpers.

    /**
     * Read-only lookup of an upload session, scoped to the calling user.
     * Per @security-compliance: ownership check (userid) is enforced in the
     * SQL clause to prevent horizontal privilege escalation. Callers with
     * the :uploadmedia capability can read THEIR sessions only, not others'.
     *
     * @param int $sessionid Local upload_session row id
     * @param int $userid     The user who must own the session
     * @return \stdClass
     * @throws \local_fastpix\exception\asset_not_found
     */
public function get_status(int $sessionid, int $userid): \stdClass {
    global $DB;
    $row = $DB->get_record(self::TABLE, [
        'id'     => $sessionid,
        'userid' => $userid,
    ]);
    if (!$row) {
        throw new \local_fastpix\exception\asset_not_found(
            "upload_session id={$sessionid} for userid={$userid}"
        );
    }
    return (object)[
        'session_id' => (int)$row->id,
        'upload_id'  => (string)$row->upload_id,
        'state'      => (string)$row->state,
        'fastpix_id' => $row->fastpix_id !== null ? (string)$row->fastpix_id : '',
        'expires_at' => (int)$row->expires_at,
    ];
}

    /**
     * List a user's ready, embeddable videos within a course, for the editor
     * picker. Owner-scoped (the uploader's own videos only), course-scoped
     * (via the upload session's courseid), ready and non-DRM (DRM videos are
     * not embeddable through the picker). Soft-deleted assets are excluded.
     *
     * @param int $courseid
     * @param int $userid
     * @return \stdClass[] Asset rows (local_fastpix_asset), newest first.
     */
public function list_ready_for_course(int $courseid, int $userid): array {
    global $DB;
    // DISTINCT collapses the duplicate rows a same-asset re-upload would produce
    // from the session join. Portable across all Moodle DBs (the asset table has
    // no TEXT/BLOB columns, and the ORDER BY column is within a.*).
    $sql = "SELECT DISTINCT a.*
              FROM {local_fastpix_asset} a
              JOIN {local_fastpix_upload_session} s ON s.fastpix_id = a.fastpix_id
             WHERE s.courseid = :courseid
               AND a.owner_userid = :userid
               AND a.status = :ready
               AND a.access_policy <> :drm
               AND a.deleted_at IS NULL
          ORDER BY a.timecreated DESC";
    $rows = $DB->get_records_sql($sql, [
        'courseid' => $courseid,
        'userid'   => $userid,
        'ready'    => 'ready',
        self::POLICY_DRM      => self::POLICY_DRM,
    ]);
    return array_values($rows);
}

    /**
     * Resolve effective access_policy for an upload.
     *   1. drm_required=true     → self::POLICY_DRM (explicit DRM intent always wins)
     *   2. caller-passed value   → caller's choice (per-call override)
     *   3. admin config default  → default_access_policy (set in settings)
     *   4. hard-coded fallback   → 'private' (defensive — fail closed)
     * Whitelist enforced: anything other than public/private/drm coming
     * from config or caller falls back to 'private'.
     *
     * @param bool $drmrequired
     * @param ?string $callervalue
     * @return string
     */
private function resolve_access_policy(bool $drmrequired, ?string $callervalue): string {
    if ($drmrequired) {
        return self::POLICY_DRM;
    }
    if ($callervalue !== null && $callervalue !== '' && in_array($callervalue, self::ACCESS_POLICIES, true)) {
        return $callervalue;
    }
    $configured = (string)get_config('local_fastpix', 'default_access_policy');
    return in_array($configured, self::ACCESS_POLICIES, true) ? $configured : self::POLICY_PRIVATE;
}

    /**
     * Resolve effective max_resolution for an upload.
     *   1. caller-passed value   → caller's choice
     *   2. admin config default  → max_resolution (set in settings)
     *   3. hard-coded fallback   → '1080p'
     *
     * @param ?string $callervalue
     * @return string
     */
private function resolve_max_resolution(?string $callervalue): string {
    $allowed = ['480p', '720p', '1080p', '1440p', '2160p'];
    if ($callervalue !== null && $callervalue !== '' && in_array($callervalue, $allowed, true)) {
        return $callervalue;
    }
    $configured = (string)get_config('local_fastpix', 'max_resolution');
    if (in_array($configured, $allowed, true)) {
        return $configured;
    }
    return '1080p';
}

    /**
     * Assert the W12 double-gate for any upload whose effective access policy
     * is self::POLICY_DRM: both feature_drm_enabled AND a non-empty drm_configuration_id
     * must be set (feature_flag_service::drm_enabled()). Non-self::POLICY_DRM policies are
     * always allowed.
     *
     * @param string $accesspolicy The resolved (effective) access policy.
     * @throws drm_not_configured when policy is self::POLICY_DRM but DRM is not configured.
     */
private function assert_drm_gate(string $accesspolicy): void {
    if ($accesspolicy === self::POLICY_DRM && !feature_flag_service::instance()->drm_enabled()) {
        throw new drm_not_configured('drm_required_but_not_configured');
    }
}

    /**
     * Dedup key.
     *
     * @param int $userid
     * @param array $metadata
     * @return string
     */
private function dedup_key(int $userid, array $metadata): string {
    $filename = (string)($metadata['filename'] ?? '');
    $size     = (int)($metadata['size'] ?? 0);
    $logical  = "upload:{$userid}:" . hash('sha256', $filename . '|' . $size);
    // The 'upload_dedup' MUC area uses simplekeys=true; hash to alphanumeric.
    return 'ud_' . substr(hash('sha256', $logical), 0, 32);
}

    /**
     * Dedup key for URL-pull sessions. Same (userid, source_url) within the
     * 60-second window returns the existing session_id with deduped=true.
     *
     * @param int $userid
     * @param string $sourceurl
     * @return string
     */
private function dedup_key_url(int $userid, string $sourceurl): string {
    $logical = "urlpull:{$userid}:" . hash('sha256', $sourceurl);
    return 'up_' . substr(hash('sha256', $logical), 0, 32);
}

    /**
     * Owner hash.
     *
     * @param int $userid
     * @return string
     */
private function owner_hash(int $userid): string {
    $salt = (string)get_config('local_fastpix', 'user_hash_salt');
    if ($salt === '') {
        // The previous fallback was: generate a random salt + set_config.
        // Removed per REVIEW-2026-05-04 §4 — concurrent first-uses produced.
        // Different salts, second worker's set_config overwrote first's,.
        // And the first worker's emitted hash silently became orphaned.
        //
        // Db/install.php bootstraps user_hash_salt at install time.
        // (random_string(32)), so an empty salt at runtime indicates:
        // - The install hook didn't run (broken install), or.
        // - someone deliberately nulled the config (operator error).
        // Both warrant failing loud so the operator notices.
        throw new \coding_exception(
            'local_fastpix: user_hash_salt config is empty; ' .
            'expected to be bootstrapped by db/install.php. ' .
            'Re-run plugin install or restore the config.'
        );
    }
    return hash_hmac('sha256', (string)$userid, $salt);
}

    /**
     * Lookup session.
     *
     * @param int $id
     * @return ?\stdClass
     */
private function lookup_session(int $id): ?\stdClass {
    global $DB;
    $row = $DB->get_record(self::TABLE, ['id' => $id]);
    return $row ?: null;
}

    /**
     * Persist session.
     *
     * @param int $userid
     * @param string $uploadid
     * @param string $uploadurl
     * @param ?string $sourceurl
     * @param int $courseid
     * @return \stdClass
     */
private function persist_session(
    int $userid,
    string $uploadid,
    string $uploadurl,
    ?string $sourceurl,
    int $courseid = 0,
): \stdClass {
    global $DB;
    $now = time();
    $row = (object)[
        'userid'      => $userid,
        'courseid'    => $courseid,
        'upload_id'   => $uploadid,
        'upload_url'  => $uploadurl,
        'fastpix_id'  => null,
        'source_url'  => $sourceurl,
        'state'       => 'pending',
        'timecreated' => $now,
        'expires_at'  => $now + self::SESSION_TTL_SECONDS,
    ];
    $row->id = $DB->insert_record(self::TABLE, $row);
    return $row;
}

    /**
     * Build response.
     *
     * @param \stdClass $session
     * @param bool $deduped
     * @return \stdClass
     */
private function build_response(\stdClass $session, bool $deduped): \stdClass {
    return (object)[
        'session_id' => (int)$session->id,
        'upload_id'  => (string)$session->upload_id,
        'upload_url' => (string)$session->upload_url,
        'expires_at' => (int)$session->expires_at,
        'deduped'    => $deduped,
    ];
}

    /**
     * SSRF guard for user-supplied source URLs.
     * Threat model: we filter URLs that resolve to private/loopback/link-local
     * IPs from Moodle's resolver at submission time. This is defense in depth.
     * What this guard does NOT cover: FastPix-side DNS rebinding. Moodle
     * never directly fetches source_url — the gateway POSTs the URL inside
     * a JSON body to api.fastpix.com, and FastPix's backend fetches it later
     * with FastPix's own resolver. CURLOPT_RESOLVE pinning on our cURL
     * handle has zero effect on FastPix's later fetch. That residual risk
     * is FastPix's to mitigate on their infrastructure; we filter obvious
     * abuse here so stale or compromised resolvers on the Moodle side
     * can't be used to probe FastPix's internal network.
     * Empirical audit 2026-05-06 (REVIEW DoD §31): zero direct-fetch sites
     * for source_url in the plugin source.
     *
     * @param string $url
     */
private function assert_ssrf_safe(string $url): void {
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https') {
        throw new ssrf_blocked('non_https');
    }
    // Reject embedded credentials (https://user:pass@host/...) — common.
    // Exfiltration vector via Referer headers and access logs, and.
    // Moodle has no use case for credential-in-URL fetches against.
    // FastPix.
    if (!empty($parts['user']) || !empty($parts['pass'])) {
        throw new ssrf_blocked('credentials_in_url');
    }
    $host = strtolower($parts['host'] ?? '');
    // Strip IPv6 literal brackets if parse_url left them in (varies by.
    // PHP version / build): https://[fe80::1]/x -> host='[fe80::1]'.
    if (strlen($host) >= 2 && $host[0] === '[' && substr($host, -1) === ']') {
        $host = substr($host, 1, -1);
    }
    if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
        throw new ssrf_blocked('local_host:' . $host);
    }

    // Direct IPv6 host literal? Validate without DNS.
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $this->assert_ip_public($host);
        return;
    }
    // Direct IPv4 host literal? Validate without DNS.
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $this->assert_ip_public($host);
        return;
    }

    foreach ($this->resolve_host_ips($host) as $ip) {
        $this->assert_ip_public($ip);
    }
}

    /**
     * Resolve a hostname to its A + AAAA record IPs for SSRF validation.
     * dns_get_record returns false on failure; treat empty/false the same as
     * gethostbynamel did. The residual TOCTOU on FastPix's later fetch is
     * documented on assert_ssrf_safe and is not a Moodle-side concern.
     *
     * @param string $host
     * @return string[]
     * @throws ssrf_blocked when the host cannot be resolved.
     */
private function resolve_host_ips(string $host): array {
    $records = @dns_get_record($host, DNS_A | DNS_AAAA);
    if ($records === false || empty($records)) {
        throw new ssrf_blocked('unresolvable:' . $host);
    }
    $ips = [];
    foreach ($records as $r) {
        if (isset($r['ip'])) {
            $ips[] = $r['ip']; // A.
        }
        if (isset($r['ipv6'])) {
            $ips[] = $r['ipv6']; // AAAA.
        }
    }
    if (empty($ips)) {
        throw new ssrf_blocked('unresolvable:' . $host);
    }
    return $ips;
}

    /**
     * Assert that an IP literal (v4 or v6) is publicly routable. Throws
     * ssrf_blocked with a tag describing the family and reason.
     * Per @upload-service guardrail: explicit byte-pattern matching for
     * private IPv6 ranges, because PHP's FILTER_FLAG_NO_PRIV_RANGE /
     * NO_RES_RANGE flags do not reliably cover all IPv6 private ranges.
     *
     * @param string $ip
     */
private function assert_ip_public(string $ip): void {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $this->assert_ipv4_public($ip);
        return;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $this->assert_ipv6_public($ip);
        return;
    }
    // Neither IPv4 nor IPv6 — reject defensively.
    throw new ssrf_blocked(self::SSRF_TAG_IP . $ip);
}

    /**
     * Assert that an IPv4 literal is publicly routable.
     * Preserves the backward-compatible error tag self::SSRF_TAG_IP.
     *
     * @param string $ip
     * @throws ssrf_blocked when private/reserved/metadata.
     */
private function assert_ipv4_public(string $ip): void {
    if (
        !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        )
    ) {
        throw new ssrf_blocked(self::SSRF_TAG_IP . $ip);
    }
    // 169.254.0.0/16 is link-local; FILTER_FLAG_NO_RES_RANGE catches it,
    // but be explicit about the AWS metadata IP for log clarity.
    if ($ip === '169.254.169.254') {
        throw new ssrf_blocked(self::SSRF_TAG_IP . $ip);
    }
}

    /**
     * Assert that an IPv6 literal is publicly routable, using explicit
     * byte-pattern matching for private ranges (PHP's NO_PRIV_RANGE /
     * NO_RES_RANGE flags do not reliably cover all IPv6 private ranges).
     *
     * @param string $ip
     * @throws ssrf_blocked when loopback/ULA/link-local/mapped-private/NAT64/metadata.
     */
private function assert_ipv6_public(string $ip): void {
    $packed = inet_pton($ip);
    if ($packed === false || strlen($packed) !== 16) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
    // Loopback ::1.
    if ($packed === inet_pton('::1')) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
    // Unspecified address (::).
    if ($packed === inet_pton('::')) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
    // ULA fc00::/7 — first byte top-7-bits = 1111110_.
    if ((ord($packed[0]) & 0xfe) === 0xfc) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
    // Link-local fe80::/10 — first 10 bits = 1111111010.
    if (ord($packed[0]) === 0xfe && (ord($packed[1]) & 0xc0) === 0x80) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
    // IPv4-mapped ::ffff:0:0/96 — first 80 bits = 0, next 16 = ffff.
    $mappedprefix = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
    if (substr($packed, 0, 12) === $mappedprefix) {
        $unpacked = unpack('N', substr($packed, 12, 4));
        if ($unpacked === false) {
            throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
        }
        $v4 = long2ip($unpacked[1]);
        $this->assert_ip_public($v4); // Recursively re-validate as IPv4.
        return;
    }
    // NAT64 64:ff9b::/96 — common synthesis prefix; trust nothing here.
    $nat64prefix = "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00";
    if (substr($packed, 0, 12) === $nat64prefix) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
    // AWS metadata over IPv6 (as documented for IMDSv2 dual-stack).
    if ($packed === inet_pton('fd00:ec2::254')) {
        throw new ssrf_blocked(self::SSRF_TAG_IPV6 . $ip);
    }
}
}
