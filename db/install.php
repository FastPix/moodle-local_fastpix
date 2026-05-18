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
 * Install-time bootstrap for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * One-time install bootstrap for local_fastpix.
 * Seeds empty webhook-secret rows (FastPix generates the actual secret;
 * admin pastes it into the settings page), seeds the user-hash salt at
 * the entropy floor required by rule S9 (64 chars), and seeds default
 * feature-flag configuration.
 * The local RS256 signing key is NOT minted here. credential_service::
 * ensure_signing_key() bootstraps it lazily on first use, after the admin
 * has saved API credentials.
 */
function xmldb_local_fastpix_install() {
    // Webhook signing secret — FastPix generates, admin pastes. Seed.
    // Empty rows so the rotation widget has a previous-slot to roll into.
    // On the first non-empty save.
    set_config('webhook_secret_current', '', 'local_fastpix');
    set_config('webhook_secret_previous', '', 'local_fastpix');
    set_config('webhook_secret_rotated_at', 0, 'local_fastpix');

    // User-hash salt for HMAC(userid) before sending to FastPix metadata.
    // 64 chars per rule S9 (sufficient entropy for HMAC-SHA256 keying).
    set_config('user_hash_salt', random_string(64), 'local_fastpix');

    // Signing-key creation timestamp — 0 means "not tracked yet".
    set_config('signing_key_created_at', 0, 'local_fastpix');

    // Feature flags — DRM off by default; require explicit opt-in.
    set_config('feature_drm_enabled', 0, 'local_fastpix');
    set_config('drm_configuration_id', '', 'local_fastpix');

    // Upload defaults (overridable per-call by mod_fastpix).
    set_config('default_access_policy', 'private', 'local_fastpix');
    set_config('max_resolution', '1080p', 'local_fastpix');

    // Tell the admin where to point FastPix's webhook configuration.
    $webhookurl = (new moodle_url('/local/fastpix/webhook.php'))->out(false);
    mtrace("local_fastpix installed. Configure FastPix webhooks to POST to:\n  {$webhookurl}");

    return true;
}
