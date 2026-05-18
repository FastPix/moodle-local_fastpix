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
    /** @var int Dedup ttl seconds. */
    private const DEDUP_TTL_SECONDS   = 60;

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
     * @return \stdClass
     */
public function create_file_upload_session(
    int $userid,
    array $metadata,
    bool $drmrequired = false,
    ?string $accesspolicy = null,
    ?string $maxresolution = null,
): \stdClass {
    $this->assert_drm_gate($drmrequired);

    // Dedup window: same (userid, filename, size) within 60s returns the.
    // Existing session.
    $cache = \cache::make('local_fastpix', 'upload_dedup');
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
     * @return \stdClass
     */
public function create_url_pull_session(
    int $userid,
    string $sourceurl,
    bool $drmrequired = false,
    ?string $accesspolicy = null,
    ?string $maxresolution = null,
): \stdClass {
    // SSRF guard runs BEFORE any gateway call (rule S6).
    $this->assert_ssrf_safe($sourceurl);
    $this->assert_drm_gate($drmrequired);

    // Dedup window: same (userid, source_url) within 60s returns the.
    // Existing session row. Mirrors the file-upload dedup contract (W11).
    $cache = \cache::make('local_fastpix', 'upload_dedup');
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
    );

    $cache->set($hashkey, $session->id);

    return $this->build_response($session, deduped: false);
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
     * resolution, DRM config id (only populated when policy='drm'), and the
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
    $drmconfigid  = $accesspolicy === 'drm'
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
     * Resolve effective access_policy for an upload.
     *   1. drm_required=true     → 'drm' (explicit DRM intent always wins)
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
        return 'drm';
    }
    $allowed = ['public', 'private', 'drm'];
    if ($callervalue !== null && $callervalue !== '' && in_array($callervalue, $allowed, true)) {
        return $callervalue;
    }
    $configured = (string)get_config('local_fastpix', 'default_access_policy');
    if (in_array($configured, $allowed, true)) {
        return $configured;
    }
    return 'private';
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
     * Assert drm gate.
     *
     * @param bool $drmrequired
     */
private function assert_drm_gate(bool $drmrequired): void {
    if ($drmrequired && !feature_flag_service::instance()->drm_enabled()) {
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
     * @return \stdClass
     */
private function persist_session(
    int $userid,
    string $uploadid,
    string $uploadurl,
    ?string $sourceurl,
): \stdClass {
    global $DB;
    $now = time();
    $row = (object)[
        'userid'      => $userid,
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
     * a JSON body to api.fastpix.io, and FastPix's backend fetches it later
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

    // Hostname: resolve A + AAAA records. dns_get_record returns false on.
    // Failure; treat empty/false the same as gethostbynamel did. The.
    // Residual TOCTOU on FastPix's later fetch is documented at the top.
    // Of this method and is not a Moodle-side concern.
    $records = @dns_get_record($host, DNS_A | DNS_AAAA);
    if ($records === false || empty($records)) {
        throw new ssrf_blocked('unresolvable:' . $host);
    }
    $ips = [];
    foreach ($records as $r) {
        if (isset($r['ip'])) {
            $ips[] = $r['ip'];
        }    // A.
        if (isset($r['ipv6'])) {
            $ips[] = $r['ipv6'];
        }  // AAAA.
    }
    if (empty($ips)) {
        throw new ssrf_blocked('unresolvable:' . $host);
    }
    foreach ($ips as $ip) {
        $this->assert_ip_public($ip);
    }
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
    // IPv4 path — preserves backward-compatible error tag 'blocked_ip:'.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (
            !filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )
        ) {
            throw new ssrf_blocked('blocked_ip:' . $ip);
        }
        // 169.254.0.0/16 Is link-local; FILTER_FLAG_NO_RES_RANGE catches it,.
        // But be explicit about the AWS metadata IP for log clarity.
        if ($ip === '169.254.169.254') {
            throw new ssrf_blocked('blocked_ip:' . $ip);
        }
        return;
    }

    // IPv6 path.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $packed = inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        // Loopback ::1.
        if ($packed === inet_pton('::1')) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        // Unspecified address (::).
        if ($packed === inet_pton('::')) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        // ULA fc00::/7 — first byte top-7-bits = 1111110_.
        if ((ord($packed[0]) & 0xfe) === 0xfc) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        // Link-local fe80::/10 — first 10 bits = 1111111010.
        if (ord($packed[0]) === 0xfe && (ord($packed[1]) & 0xc0) === 0x80) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        // IPv4-mapped ::ffff:0:0/96 — first 80 bits = 0, next 16 = ffff.
        $mappedprefix = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
        if (substr($packed, 0, 12) === $mappedprefix) {
            $unpacked = unpack('N', substr($packed, 12, 4));
            if ($unpacked === false) {
                throw new ssrf_blocked('blocked_ipv6:' . $ip);
            }
            $v4 = long2ip($unpacked[1]);
            $this->assert_ip_public($v4); // Recursively re-validate as IPv4.
            return;
        }
        // NAT64 64:ff9b::/96 — common synthesis prefix; trust nothing here.
        $nat64prefix = "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00";
        if (substr($packed, 0, 12) === $nat64prefix) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        // AWS metadata over IPv6 (as documented for IMDSv2 dual-stack).
        if ($packed === inet_pton('fd00:ec2::254')) {
            throw new ssrf_blocked('blocked_ipv6:' . $ip);
        }
        return; // Public IPv6.
    }

    // Neither IPv4 nor IPv6 — reject defensively.
    throw new ssrf_blocked('blocked_ip:' . $ip);
}
}
