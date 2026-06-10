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
 * External (web service) function: add a manual subtitle track.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\external;

/**
 * External function: attach a manual .vtt subtitle track to a ready asset.
 * The .vtt is supplied as a public HTTPS URL FastPix can fetch; the URL is
 * SSRF-guarded in the service before the gateway call (rule S6). Owner-scoped:
 * a caller may only add tracks to media they own.
 * Registered in db/services.php as 'local_fastpix_add_subtitle_track'.
 * Capability: mod/fastpix:uploadmedia (per ADR-012, owned by mod_fastpix).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_subtitle_track extends \core_external\external_api {
    /**
     * Web service parameter spec.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'contextid' => new \core_external\external_value(
                PARAM_INT,
                'Context id of the course the media belongs to',
                VALUE_REQUIRED
            ),
            'mediaid' => new \core_external\external_value(
                PARAM_ALPHANUMEXT,
                'FastPix media id (asset fastpix_id) the track is added to',
                VALUE_REQUIRED
            ),
            'languagecode' => new \core_external\external_value(
                PARAM_ALPHANUMEXT,
                'Language code of the subtitle track',
                VALUE_REQUIRED
            ),
            'vtt' => new \core_external\external_value(
                PARAM_URL,
                'Public HTTPS URL of the .vtt file FastPix will fetch',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Add a manual subtitle track.
     *
     * @param int    $contextid    Course context id the media belongs to
     * @param string $mediaid      FastPix media id
     * @param string $languagecode Subtitle language code
     * @param string $vtt          Public HTTPS URL of the .vtt
     * @return array{trackid:string}
     */
    public static function execute(
        int $contextid,
        string $mediaid,
        string $languagecode,
        string $vtt
    ): array {
        global $USER;

        // 1. Validate parameters first (throws invalid_parameter_exception).
        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'contextid'    => $contextid,
                'mediaid'      => $mediaid,
                'languagecode' => $languagecode,
                'vtt'          => $vtt,
            ]
        );

        // 2. Authenticate + authorize against the COURSE context.
        $context = \core\context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_login(null, false);
        require_sesskey();
        require_capability('mod/fastpix:uploadmedia', $context);

        // 3. Delegate. Owner-scope, language validation and the SSRF guard on
        //    the .vtt URL all live in the service.
        $result = \local_fastpix\service\upload_service::instance()
            ->add_subtitle_track(
                (int)$USER->id,
                $params['mediaid'],
                $params['languagecode'],
                $params['vtt'],
            );

        // 4. Return matches execute_returns() structure.
        return ['trackid' => (string)$result->track_id];
    }

    /**
     * Web service return spec.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'trackid' => new \core_external\external_value(
                PARAM_TEXT,
                'FastPix track id of the added subtitle track'
            ),
        ]);
    }
}
