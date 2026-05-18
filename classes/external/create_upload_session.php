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
            'filename' => new \core_external\external_value(
                PARAM_TEXT,
                'Original filename of the upload (used for dedup hashing only)',
                VALUE_REQUIRED
            ),
            'size' => new \core_external\external_value(
                PARAM_INT,
                'File size in bytes (used for dedup hashing only)',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Create a direct upload session.
     *
     * @param string $filename Original filename
     * @param int    $size     File size in bytes
     * @return array{session_id:int,upload_id:string,upload_url:string,expires_at:int,deduped:bool}
     */
    public static function execute(string $filename, int $size): array {
        global $USER;

        // 1. Validate parameters first (throws invalid_parameter_exception).
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['filename' => $filename, 'size' => $size]
        );

        // 2. Authenticate + authorize.
        $context = \context_system::instance();
        require_login(null, false);
        require_sesskey();
        require_capability('mod/fastpix:uploadmedia', $context);

        // 3. Delegate to service layer.
        $result = \local_fastpix\service\upload_service::instance()
            ->create_file_upload_session(
                (int)$USER->id,
                [
                'filename' => $params['filename'],
                'size'     => $params['size'],
                ]
            );

        // 4. Return matches execute_returns() structure.
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
                'Signed upload URL (Google Cloud Storage); empty for URL pull'
            ),
            'expires_at' => new \core_external\external_value(
                PARAM_INT,
                'Unix timestamp at which upload_url expires'
            ),
            'deduped' => new \core_external\external_value(
                PARAM_BOOL,
                'True if this session was returned from the 60s dedup cache'
            ),
        ]);
    }
}
