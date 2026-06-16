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
            'contextid' => new \core_external\external_value(
                PARAM_INT,
                'Context id of the course the upload belongs to',
                VALUE_REQUIRED
            ),
            'source_url' => new \core_external\external_value(
                PARAM_URL,
                'Public HTTPS URL of the source video (FastPix will fetch from here)',
                VALUE_REQUIRED
            ),
            // The following mirror create_upload_session's settings. They are
            // VALUE_DEFAULT (not REQUIRED) so existing callers that pass only
            // contextid + source_url keep working (backward compatible).
            'title' => new \core_external\external_value(
                PARAM_TEXT,
                'Title applied to the pulled video (the activity name)',
                VALUE_DEFAULT,
                ''
            ),
            'accesspolicy' => new \core_external\external_value(
                PARAM_ALPHA,
                'Access policy: private, public, or drm',
                VALUE_DEFAULT,
                ''
            ),
            'captionsmode' => new \core_external\external_value(
                PARAM_ALPHA,
                'Captions: none, auto (Whisper), or vtt (manual)',
                VALUE_DEFAULT,
                'none'
            ),
            'languagecode' => new \core_external\external_value(
                PARAM_ALPHANUMEXT,
                'Spoken-language code for auto captions; required when captionsmode=auto',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Create a URL-pull session.
     *
     * @param int    $contextid    Course context id the upload belongs to
     * @param string $sourceurl    Public HTTPS URL FastPix will fetch from
     * @param string $title        Title applied to the pulled video
     * @param string $accesspolicy private | public | drm (empty = admin default)
     * @param string $captionsmode none | auto | vtt
     * @param string $languagecode Spoken-language code (auto captions only)
     * @return array{session_id:int,upload_id:string,upload_url:string,expires_at:int,deduped:bool}
     */
    public static function execute(
        int $contextid,
        string $sourceurl,
        string $title = '',
        string $accesspolicy = '',
        string $captionsmode = 'none',
        string $languagecode = ''
    ): array {
        global $USER;

        // 1. Validate parameters first (throws invalid_parameter_exception).
        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'contextid'    => $contextid,
                'source_url'   => $sourceurl,
                'title'        => $title,
                'accesspolicy' => $accesspolicy,
                'captionsmode' => $captionsmode,
                'languagecode' => $languagecode,
            ]
        );

        // 2. Authenticate + authorize against the COURSE context the upload
        // belongs to. mod/fastpix:uploadmedia is a CONTEXT_COURSE capability
        // (ADR-012, owned by mod_fastpix); checking it at system context
        // denied editing teachers while only admins (who bypass checks) passed.
        // get_course_context() normalises a course OR module context to its
        // course and throws if there is none (system context → no uploads).
        $context = \core\context::instance_by_id($params['contextid']);
        self::validate_context($context);
        $coursecontext = $context->get_course_context();
        require_login(null, false);
        require_sesskey();
        upload_capability_guard::require_upload_capability($coursecontext);

        // 3. Delegate to service layer. SSRF allow-list runs INSIDE the service.
        // BEFORE the gateway call (rule S6, @upload-service guardrail).
        // Ssrf_blocked exceptions propagate to the caller as service errors.
        // The courseid scopes the upload for the editor picker. Title, access
        // policy and captions are forwarded so URL-pulled videos honour the
        // activity's Media settings (parity with create_upload_session). An
        // empty accesspolicy lets the service apply the admin default.
        $result = \local_fastpix\service\upload_service::instance()
            ->create_url_pull_session(
                (int)$USER->id,
                $params['source_url'],
                accesspolicy: $params['accesspolicy'] !== '' ? $params['accesspolicy'] : null,
                courseid: (int)$coursecontext->instanceid,
                title: $params['title'],
                captionsmode: $params['captionsmode'],
                languagecode: $params['languagecode'] !== '' ? $params['languagecode'] : null,
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
