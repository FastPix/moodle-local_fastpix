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
 * External (web service) function: create upload session.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * External function: create a direct file upload session against FastPix.
 * Per architecture doc §3.3 (Direct upload session) and §15.4 (upload service).
 * Per @upload-service agent: this endpoint is responsible for capability
 * + sesskey + login enforcement. The service layer does NOT enforce them.
 * Registered in db/services.php as 'local_fastpix_create_upload_session'.
 * Capability: mod/fastpix:uploadmedia (per ADR-012, owned by mod_fastpix).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_upload_session extends \core_external\external_api {
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
            'title' => new \core_external\external_value(
                PARAM_TEXT,
                'Title applied to the uploaded video',
                VALUE_REQUIRED
            ),
            'accesspolicy' => new \core_external\external_value(
                PARAM_ALPHA,
                'Access policy: private, public, or drm',
                VALUE_REQUIRED
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
     * Create a direct upload session with the chosen settings.
     *
     * @param int    $contextid    Course context id the upload belongs to
     * @param string $title        Title applied to the uploaded video
     * @param string $accesspolicy private | public | drm
     * @param string $captionsmode none | auto | vtt
     * @param string $languagecode Spoken-language code (auto captions only)
     * @return array{uploadurl:string,uploadid:string}
     */
    public static function execute(
        int $contextid,
        string $title,
        string $accesspolicy,
        string $captionsmode = 'none',
        string $languagecode = ''
    ): array {
        global $USER;

        // 1. Validate parameters first (throws invalid_parameter_exception).
        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'contextid'    => $contextid,
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

        // 3. Delegate to service layer. DRM gating, language validation and the
        // SSRF-free pushMediaSettings mapping all live in the service. The
        // courseid scopes the upload for the editor picker.
        $result = \local_fastpix\service\upload_service::instance()
            ->create_direct_upload_with_settings(
                (int)$USER->id,
                $params['title'],
                $params['accesspolicy'],
                $params['captionsmode'],
                $params['languagecode'] !== '' ? $params['languagecode'] : null,
                (int)$coursecontext->instanceid,
            );

        // 4. Credentials never leave the server — only the signed upload URL.
        // session_id is the integer the consumer stores (mdl_fastpix.
        // upload_session_id is PARAM_INT) and later resolves the asset with
        // via asset_service::get_by_upload_session_id(); the UUID uploadid
        // would be truncated by PARAM_INT (see ADR-015).
        return [
            'session_id' => (int)$result->session_id,
            'uploadurl'  => (string)$result->upload_url,
            'uploadid'   => (string)$result->upload_id,
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
                'Local upload session row id; store this and resolve the asset '
                . 'via get_by_upload_session_id once the webhook lands'
            ),
            'uploadurl' => new \core_external\external_value(
                PARAM_RAW,
                'Signed upload URL the browser PUTs the file to'
            ),
            'uploadid' => new \core_external\external_value(
                PARAM_TEXT,
                'FastPix upload ID (UUID)'
            ),
        ]);
    }
}
