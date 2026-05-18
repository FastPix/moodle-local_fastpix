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
 * API integration: gateway.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\api;

use local_fastpix\exception\gateway_unavailable;
use local_fastpix\exception\gateway_not_found;
use local_fastpix\exception\gateway_invalid_response;
use local_fastpix\service\credential_service;

/**
 * FastPix HTTP gateway.
 * The single trusted boundary between local_fastpix and api.fastpix.io.
 * Owns retry, circuit breaker, idempotency keys, two timeout profiles,
 * structured logging, and credential injection. Rule A2: no other class
 * may make HTTP calls to FastPix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway {
    /** @var array Profile hot. */
    private const PROFILE_HOT      = ['connect' => 3, 'read' => 3];
    /** @var array Profile standard. */
    private const PROFILE_STANDARD = ['connect' => 5, 'read' => 30];
    /** @var array Profile health. */
    private const PROFILE_HEALTH   = ['connect' => 1, 'read' => 1];

    /** @var array Retry delays ms. */
    private const RETRY_DELAYS_MS    = [200, 400, 800];
    /** @var int Retry jitter ms. */
    private const RETRY_JITTER_MS    = 25;
    /** @var int Retry max attempts. */
    private const RETRY_MAX_ATTEMPTS = 3;
    /** @var int Retry after cap ms. */
    private const RETRY_AFTER_CAP_MS = 3000;

    /** @var int Breaker threshold. */
    private const BREAKER_THRESHOLD    = 5;
    /** @var int Breaker open seconds. */
    private const BREAKER_OPEN_SECONDS = 30;

    /** @var string Default base url. */
    private const DEFAULT_BASE_URL = 'https://api.fastpix.io';

    /**
     * Max response body length the gateway will decode (defensive).
     */    private const MAX_RESPONSE_BYTES = 5242880; // 5 MiB.

    /** @var ?self $instance */
    /** @var mixed */
    private static ?self $instance = null;

    /**
     * Constructor.
     *
     * @param \core\http_client $http The HTTP client used for FastPix requests.
     * @param \cache_application $breakercache MUC cache holding circuit-breaker state.
     * @param credential_service $credentials Credential lookup for apikey/apisecret.
     */
    private function __construct(
        /** @var \core\http_client The HTTP client used for FastPix requests. */
        private \core\http_client $http,
        /** @var \cache_application MUC cache holding circuit-breaker state. */
        private \cache_application $breakercache,
        /** @var credential_service Credential lookup for apikey/apisecret. */
        private credential_service $credentials,
    ) {
    }

    /**
     * Singleton accessor.
     *
     * @return self
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self(
                new \core\http_client(),
                \cache::make('local_fastpix', 'circuit_breaker'),
                credential_service::instance(),
            );
        }
        return self::$instance;
    }

    /**
     * Reset the singleton (used by tests).
     */    public static function reset(): void {
        self::$instance = null;
}

    // Public API.

    /**
     * POST /v1/on-demand/upload — create a direct file-upload session.
     *
     * @param string $ownerhash
     * @param array $metadata
     * @param string $accesspolicy
     * @param ?string $drmconfigid
     * @param string $maxresolution
     * @return \stdClass
     */
public function input_video_direct_upload(
    string $ownerhash,
    array $metadata,
    string $accesspolicy,
    ?string $drmconfigid,
    string $maxresolution = '1080p',
): \stdClass {
    $body = [
        'corsOrigin'   => '*',
        'pushMediaSettings' => [
            'metadata'      => $metadata,
            'accessPolicy'  => $accesspolicy,
            'maxResolution' => $maxresolution,
        ],
    ];
    if ($drmconfigid !== null && $drmconfigid !== '') {
        $body['pushMediaSettings']['drmConfigurationId'] = $drmconfigid;
    }

    return $this->request(
        'POST',
        '/v1/on-demand/upload',
        $body,
        self::PROFILE_STANDARD,
        $this->idempotency_key('input_video_direct_upload', $ownerhash, $body),
    );
}

    /**
     * POST /v1/on-demand — create a media asset from a remote URL.
     *
     * @param string $sourceurl
     * @param string $ownerhash
     * @param array $metadata
     * @param string $accesspolicy
     * @param ?string $drmconfigid
     * @param string $maxresolution
     * @return \stdClass
     */
public function media_create_from_url(
    string $sourceurl,
    string $ownerhash,
    array $metadata,
    string $accesspolicy,
    ?string $drmconfigid,
    string $maxresolution = '1080p',
): \stdClass {
    $body = [
        'inputs'        => [['type' => 'video', 'url' => $sourceurl]],
        'metadata'      => $metadata,
        'accessPolicy'  => $accesspolicy,
        'maxResolution' => $maxresolution,
    ];
    if ($drmconfigid !== null && $drmconfigid !== '') {
        $body['drmConfigurationId'] = $drmconfigid;
    }

    return $this->request(
        'POST',
        '/v1/on-demand',
        $body,
        self::PROFILE_STANDARD,
        $this->idempotency_key('media_create_from_url', $ownerhash, $body),
    );
}

    /**
     * GET /v1/on-demand/{mediaId} — hot path. PROFILE_HOT (3s/3s).
     * 404 → gateway_not_found IMMEDIATELY, no retry.
     *
     * @param string $fastpixid
     * @return \stdClass
     */
public function get_media(string $fastpixid): \stdClass {
    return $this->request(
        'GET',
        '/v1/on-demand/' . rawurlencode($fastpixid),
        null,
        self::PROFILE_HOT,
        null,
    );
}

    /**
     * DELETE /v1/on-demand/{mediaId}. 404 returns silently (idempotent).
     *
     * @param string $fastpixid
     */
public function delete_media(string $fastpixid): void {
    $this->request(
        'DELETE',
        '/v1/on-demand/' . rawurlencode($fastpixid),
        null,
        self::PROFILE_STANDARD,
        $this->idempotency_key('delete_media', $fastpixid, null),
    );
}

    /**
     * POST /v1/iam/signing-keys — provision a new RSA signing key.
     * Returns object with id (kid), privateKey (base64 PEM), createdAt.
     *
     * @return \stdClass
     */
public function create_signing_key(): \stdClass {
    return $this->request(
        'POST',
        '/v1/iam/signing-keys',
        null,
        self::PROFILE_STANDARD,
        $this->idempotency_key('create_signing_key', '', null),
    );
}

    /**
     * DELETE /v1/iam/signing-keys/{kid}. 404 silent.
     *
     * @param string $kid
     */
public function delete_signing_key(string $kid): void {
    $this->request(
        'DELETE',
        '/v1/iam/signing-keys/' . rawurlencode($kid),
        null,
        self::PROFILE_STANDARD,
        $this->idempotency_key('delete_signing_key', $kid, null),
    );
}

    /**
     * Health probe. Returns false on any failure. NEVER throws.
     *
     * @return bool
     */
public function health_probe(): bool {
    try {
        $response = $this->http->request('GET', $this->base_url() . '/v1/on-demand', [
            'connect_timeout' => self::PROFILE_HEALTH['connect'],
            'timeout'         => self::PROFILE_HEALTH['read'],
            'auth'            => [$this->credentials->apikey(), $this->credentials->apisecret()],
            'headers'          => $this->build_headers(null),
            'query'            => ['limit' => 1],
            'http_errors'      => false,
            'force_ip_resolve' => 'v4',
        ]);
        $status = $response->getStatusCode();
        return $status >= 200 && $status < 300;
    } catch (\Throwable $e) {
        return false;
    }
}

    // Private helpers.

    /**
     * Request.
     *
     * @param string $method
     * @param string $path
     * @param ?array $body
     * @param array $profile
     * @param ?string $idempotencykey
     * @return \stdClass
     */
private function request(
    string $method,
    string $path,
    ?array $body,
    array $profile,
    ?string $idempotencykey,
): \stdClass {
    $endpointkey = $this->endpoint_key($method, $path);
    $requestid   = 'req_' . random_string(12);
    $host         = $this->host_from_base();

    if ($this->breaker_is_open($endpointkey)) {
        $this->log_call($endpointkey, 0, 0, 0, $profile, 'open', $method, $host, $path, $requestid);
        throw new gateway_unavailable("circuit_open:{$endpointkey}");
    }

    $start = microtime(true);
    $attempt = 0;
    $lasterror = null;
    $lastbody  = '';
    $delayms = 0;

    while ($attempt < self::RETRY_MAX_ATTEMPTS) {
        $attempt++;

        try {
            $response = $this->http->request($method, $this->base_url() . $path, [
                'connect_timeout'  => $profile['connect'],
                'timeout'          => $profile['read'],
                'auth'             => [$this->credentials->apikey(), $this->credentials->apisecret()],
                'headers'          => $this->build_headers($idempotencykey, $requestid),
                'json'             => $body,
                'http_errors'      => false,
                // FastPix's CDN advertises AAAA records; many container.
                // Bridge networks have no IPv6 route. Pin to v4 so.
                // Happy-Eyeballs doesn't intermittently pick a route.
                // That immediately fails with ConnectException.
                'force_ip_resolve' => 'v4',
            ]);

            $status = $response->getStatusCode();
            $latencyms = (int)((microtime(true) - $start) * 1000);
            $this->log_call($endpointkey, $latencyms, $status, $attempt, $profile, 'closed', $method, $host, $path, $requestid);

            if ($status >= 200 && $status < 300) {
                $this->breaker_record_success($endpointkey);
                return $this->decode_body($response);
            }

            // Capture the body once per response so retries_exhausted carries.
            // The LAST upstream diagnostic. Body is NOT logged anywhere — only.
            // Attached to the thrown exception's $a context for the caller.
            $lastbody = $this->body_snippet($response);

            if ($status === 404) {
                if ($method === 'GET' && str_starts_with($path, '/v1/on-demand/')) {
                    throw new gateway_not_found("{$path} body={$lastbody}");
                }
                if ($method === 'DELETE') {
                    $this->breaker_record_success($endpointkey);
                    return new \stdClass();
                }
            }

            if (!$this->is_retryable($status)) {
                $this->breaker_record_failure($endpointkey);
                throw new gateway_unavailable("status_{$status}:{$endpointkey} body={$lastbody}");
            }

            $delayms = $status === 429
                ? min(self::RETRY_AFTER_CAP_MS, $this->parse_retry_after($response) * 1000)
                : self::RETRY_DELAYS_MS[$attempt - 1] + random_int(-self::RETRY_JITTER_MS, self::RETRY_JITTER_MS);
            $lasterror = "status_{$status}";
        } catch (gateway_not_found $e) {
            throw $e;
        } catch (gateway_unavailable $e) {
            throw $e;
        } catch (gateway_invalid_response $e) {
            // Response_too_large / json_decode_failed — not transient, no retry.
            throw $e;
        } catch (\Throwable $e) {
            $lasterror = 'network_' . (new \ReflectionClass($e))->getShortName();
            $delayms = self::RETRY_DELAYS_MS[$attempt - 1] + random_int(-self::RETRY_JITTER_MS, self::RETRY_JITTER_MS);
            $this->log_call(
                $endpointkey,
                (int)((microtime(true) - $start) * 1000),
                0,
                $attempt,
                $profile,
                'closed',
                $method,
                $host,
                $path,
                $requestid,
            );
        }

        if ($attempt < self::RETRY_MAX_ATTEMPTS && $delayms > 0) {
            usleep($delayms * 1000);
        }
    }

    $this->breaker_record_failure($endpointkey);
    $bodytag = $lastbody !== '' ? " body={$lastbody}" : '';
    throw new gateway_unavailable("retries_exhausted:{$lasterror}:{$endpointkey}{$bodytag}");
}

    /**
     * Return up to 500 chars of an HTTP response body, with an ellipsis when
     * truncated. Used to attach upstream diagnostics to gateway_unavailable /
     * gateway_not_found exceptions. The body is NEVER passed to error_log —
     * only carried on the exception so callers can surface it in their own
     * logging or admin UI. Cost ~30 min debugging on 2026-05-04 (REVIEW T2.2).
     *
     * @param mixed $response
     * @return string
     */
private function body_snippet($response): string {
    $raw = (string)$response->getBody();
    if (strlen($raw) <= 500) {
        return $raw;
    }
    return substr($raw, 0, 500) . '...';
}

    /**
     * Whether retryable.
     *
     * @param int $status
     * @return bool
     */
private function is_retryable(int $status): bool {
    // 408 (Request Timeout) is transient under sustained load; retry per.
    // FastPix docs.
    return in_array($status, [408, 429, 500, 502, 503, 504], true);
}

    /**
     * Parse retry after.
     *
     * @param mixed $response
     * @return int
     */
private function parse_retry_after($response): int {
    $header = $response->getHeaderLine('Retry-After');
    if ($header === '' || !ctype_digit(trim($header))) {
        return 1;
    }
    return max(0, (int)$header);
}

    /**
     * Decode body.
     *
     * @param mixed $response
     * @return \stdClass
     */
private function decode_body($response): \stdClass {
    // Defensive bound against malicious upstream returning unbounded bytes.
    $advertised = (int)$response->getHeaderLine('Content-Length');
    if ($advertised > self::MAX_RESPONSE_BYTES) {
        throw new gateway_invalid_response('response_too_large');
    }
    $raw = (string)$response->getBody();
    if (strlen($raw) > self::MAX_RESPONSE_BYTES) {
        throw new gateway_invalid_response('response_too_large');
    }
    if ($raw === '') {
        return new \stdClass();
    }
    $decoded = json_decode($raw);
    if (!($decoded instanceof \stdClass) && !is_array($decoded)) {
        throw new gateway_invalid_response('json_decode_failed');
    }
    return is_array($decoded) ? (object)['data' => $decoded] : $decoded;
}

    /**
     * Build headers.
     *
     * @param ?string $idempotencykey
     * @param ?string $requestid
     * @return array
     */
private function build_headers(?string $idempotencykey, ?string $requestid = null): array {
    $headers = [
        'Accept'     => 'application/json',
        'User-Agent' => 'local_fastpix/' . (string)get_config('local_fastpix', 'version'),
    ];
    if ($idempotencykey !== null) {
        $headers['Idempotency-Key'] = $idempotencykey;
    }
    if ($requestid !== null && $requestid !== '') {
        $headers['X-Request-Id'] = $requestid;
    }
    return $headers;
}

    /**
     * Extract the host portion of base_url() for structured logging.
     *
     * @return string
     */
private function host_from_base(): string {
    $host = parse_url($this->base_url(), PHP_URL_HOST);
    return is_string($host) ? $host : '';
}

    /**
     * Idempotency key.
     *
     * @param string $operation
     * @param string $ownerhash
     * @param ?array $body
     * @return string
     */
private function idempotency_key(string $operation, string $ownerhash, ?array $body): string {
    $payloadhash = $body !== null ? hash('sha256', json_encode($body)) : '-';
    return hash('sha256', "{$operation}:{$ownerhash}:{$payloadhash}");
}

    /**
     * Endpoint key.
     *
     * @param string $method
     * @param string $path
     * @return string
     */
private function endpoint_key(string $method, string $path): string {
    // MUC area 'circuit_breaker' is declared simplekeys=true, so the key must be.
    // Alphanumeric only. SHA-256 prefix (32 hex chars) gives 128 bits of collision.
    // Resistance — replacing CRC32 (32-bit) per REVIEW-2026-05-04 §S-1.
    return substr(hash('sha256', $method . ':' . $path), 0, 32);
}

    /**
     * Base url.
     *
     * @return string
     */
private function base_url(): string {
    $configured = (string)get_config('local_fastpix', 'fastpix_base_url');
    return $configured !== '' ? rtrim($configured, '/') : self::DEFAULT_BASE_URL;
}

    // Circuit breaker (MUC-backed; multi-FPM correctness).

    /**
     * Breaker is open.
     *
     * @param string $key
     * @return bool
     */
private function breaker_is_open(string $key): bool {
    $state = $this->breakercache->get($key);
    if (!is_array($state)) {
        return false;
    }
    return ($state['open_until'] ?? 0) > time();
}

    /**
     * Breaker record failure.
     *
     * @param string $key
     */
private function breaker_record_failure(string $key): void {
    $state = $this->breakercache->get($key);
    if (!is_array($state)) {
        $state = ['failures' => 0, 'open_until' => 0];
    }
    $state['failures'] = ($state['failures'] ?? 0) + 1;
    if ($state['failures'] >= self::BREAKER_THRESHOLD) {
        $state['open_until'] = time() + self::BREAKER_OPEN_SECONDS;
    }
    $this->breakercache->set($key, $state);
}

    /**
     * Breaker record success.
     *
     * @param string $key
     */
private function breaker_record_success(string $key): void {
    $this->breakercache->delete($key);
}

    // Structured logging (no secrets).

    /**
     * Log call.
     *
     * @param string $endpointkey
     * @param int $latencyms
     * @param int $status
     * @param int $attempt
     * @param array $profile
     * @param string $circuitstate
     * @param string $method
     * @param string $host
     * @param string $path
     * @param string $requestid
     */
private function log_call(
    string $endpointkey,
    int $latencyms,
    int $status,
    int $attempt,
    array $profile,
    string $circuitstate,
    string $method = '',
    string $host = '',
    string $path = '',
    string $requestid = '',
): void {
    $profilename = $profile === self::PROFILE_HOT
        ? 'hot'
        : ($profile === self::PROFILE_HEALTH ? 'health' : 'standard');

    $pathlogged = $path === '' ? '' : strtok($path, '?');

    // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
    error_log(json_encode([
        'event'           => 'gateway.call',
        'request_id'      => $requestid,
        'method'          => $method,
        'host'            => $host,
        'path'            => $pathlogged,
        'endpoint'        => $endpointkey,
        'latency_ms'      => $latencyms,
        'status_code'     => $status,
        'attempt'         => $attempt,
        'circuit_state'   => $circuitstate,
        'timeout_profile' => $profilename,
    ]));
}
}
