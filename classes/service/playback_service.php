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
 * Service: playback service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

use local_fastpix\dto\playback_payload;
use local_fastpix\exception\asset_not_found;
use local_fastpix\exception\asset_not_ready;

/**
 * Public chokepoint for playback-token minting.
 * Authorized by ADR-013. The four sibling plugins (mod_fastpix,
 * filter_fastpix, tinymce_fastpix, future viewer) MUST go through
 * this service to obtain a JWT — direct use of jwt_signing_service
 * is a PR-3 violation (consumer contract CC1).
 * Read-path. No gateway call (rule W7), no DB write. The asset is
 * looked up via asset_service::get_by_fastpix_id which is MUC-cached;
 * the JWT mint itself is 1–5ms and never cached (rule S10).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class playback_service {
    /**
     * Resolve a playback payload for a known, ready, non-deleted asset.
     * The caller is trusted (mod_fastpix has already done require_login,
     * capability check, and activity-context verification). $userid is
     * accepted to match the consumer-contract signature and reserved
     * for future per-user policy hooks (rate limit, watermark
     * personalisation); unused today.
     *
     * @throws asset_not_found  if asset missing or soft-deleted
     * @throws asset_not_ready  if asset exists but status != 'ready'
     * @throws \local_fastpix\exception\signing_key_missing  bubbled from JWT mint
     * @param string $fastpixid
     * @param int $userid
     * @return playback_payload
     */
    public static function resolve(string $fastpixid, int $userid): playback_payload {
        $asset = asset_service::get_by_fastpix_id($fastpixid);
        if ($asset === null) {
            throw new asset_not_found($fastpixid);
        }
        if ($asset->status !== 'ready') {
            throw new asset_not_ready($fastpixid . ' (status=' . $asset->status . ')');
        }

        $signer = new jwt_signing_service();
        $jwt = $signer->sign_for_playback((string)$asset->playback_id);
        $ttl = $signer->token_ttl_seconds();

        return playback_payload::from_asset_and_jwt(
            $asset,
            $jwt,
            $ttl,
            null,
            false,
        );
    }
}
