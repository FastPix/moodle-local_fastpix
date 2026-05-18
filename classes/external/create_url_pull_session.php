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
 * External (web service) function: create url pull session.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * External function: create a URL-pull ingest session against FastPix.
 * FastPix downloads from the source URL on its own infrastructure rather
 * than the user uploading bytes directly. The signed upload_url returned
 * is empty (PARAM_RAW) — there is no GCS URL to PUT to.
 * Per architecture doc §3.3 (URL pull) and §15.4 (upload service).
 * Per @upload-service agent: SSRF check happens in the service BEFORE
 * the gateway call. This endpoint does not duplicate the check.
 * Registered in db/services.php as 'local_fastpix_create_url_pull_session'.
 * Capability: mod/fastpix:uploadmedia (per ADR-012, owned by mod_fastpix).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_url_pull_session extends \core_external\external_api {
    /**
     * Web service parameter spec.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'source_url' => new \core_external\external_value(
                PARAM_URL,
                'Public HTTPS URL of the source video (FastPix will fetch from here)',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Create a URL-pull session.
     *
     * @param string $sourceurl Public HTTPS URL FastPix will fetch from
     * @return array{session_id:int,upload_id:string,upload_url:string,expires_at:int,deduped:bool}
     */
    public static function execute(string $sourceurl): array {
        global $USER;

        // 1. Validate parameters first (throws invalid_parameter_exception).
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['source_url' => $sourceurl]
        );

        // 2. Authenticate + authorize.
        $context = \context_system::instance();
        require_login(null, false);
        require_sesskey();
        require_capability('mod/fastpix:uploadmedia', $context);

        // 3. Delegate to service layer. SSRF allow-list runs INSIDE the service.
        // BEFORE the gateway call (rule S6, @upload-service guardrail).
        // Ssrf_blocked exceptions propagate to the caller as service errors.
        $result = \local_fastpix\service\upload_service::instance()
            ->create_url_pull_session(
                (int)$USER->id,
                $params['source_url']
            );

        // 4. Return matches execute_returns() structure.
        // Upload_url is empty for URL-pull sessions — no GCS URL to PUT to.
        return [
        'session_id' => (int)$result->session_id,
        'upload_id'  => (string)$result->upload_id,
        'upload_url' => (string)$result->upload_url,
        'expires_at' => (int)$result->expires_at,
        'deduped'    => (bool)$result->deduped,
        ];
    }

    /**
     * Web service return spec.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'session_id' => new \core_external\external_value(
                PARAM_INT,
                'Local upload session row id'
            ),
            'upload_id' => new \core_external\external_value(
                PARAM_TEXT,
                'FastPix upload ID (UUID)'
            ),
            'upload_url' => new \core_external\external_value(
                PARAM_RAW,
                'Empty string for URL-pull sessions (FastPix fetches the source itself)'
            ),
            'expires_at' => new \core_external\external_value(
                PARAM_INT,
                'Unix timestamp at which the session expires'
            ),
            'deduped' => new \core_external\external_value(
                PARAM_BOOL,
                'True if this session was returned from the 60s dedup cache'
            ),
        ]);
    }
}
