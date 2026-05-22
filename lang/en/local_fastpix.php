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
 * Language strings for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.Files.LangFilesOrdering

$string['pluginname'] = 'FastPix';

// Exception strings (constructor $context interpolated as {$a}).
$string['gateway_unavailable'] = 'FastPix gateway unavailable: {$a}';
$string['gateway_invalid_response'] = 'FastPix gateway returned an invalid response: {$a}';
$string['gateway_not_found'] = 'FastPix media not found: {$a}';
$string['signing_key_missing'] = 'JWT signing key is missing or invalid: {$a}';
$string['hmac_invalid'] = 'Webhook signature verification failed: {$a}';
$string['lock_acquisition_failed'] = 'Could not acquire per-asset lock: {$a}';
$string['asset_not_found'] = 'Asset not found: {$a}';
$string['drm_not_configured'] = 'DRM is not configured: {$a}';
$string['ssrf_blocked'] = 'URL rejected by SSRF guard: {$a}';
$string['rate_limit_exceeded'] = 'Rate limit exceeded: {$a}';
$string['credentials_missing'] = 'FastPix credentials are not configured: {$a}';

// Capability labels.
$string['fastpix:configurecredentials'] = 'Configure FastPix API credentials';

// Scheduled task names.
$string['task_orphan_sweeper'] = 'Sweep expired upload sessions';
$string['task_webhook_event_pruner'] = 'Prune old processed webhook events';
$string['task_asset_cleanup'] = 'Hard-delete soft-deleted assets past GDPR retention';
$string['task_signing_key_rotator'] = 'Rotate JWT signing key every 90 days';
$string['task_retry_gdpr_delete'] = 'Retry pending GDPR remote deletions';

// Adhoc task name.
$string['task_process_webhook'] = 'Process a single FastPix webhook event';

// Web service descriptions.
$string['ws_create_upload_session'] = 'Create a FastPix file upload session';
$string['ws_create_url_pull_session'] = 'Create a FastPix URL pull session';
$string['ws_get_upload_status'] = 'Get the status of a FastPix upload session';

// Admin settings page.
$string['setting_apikey']             = 'API Key';
$string['setting_apikey_desc']        = 'Enter the FastPix API Key from your FastPix Dashboard. Create or manage keys under Settings → API Keys.';
$string['setting_apisecret']          = 'API Secret';
$string['setting_apisecret_desc']     = 'Enter the API Secret associated with the API Key above. This value is hidden in the UI but stored in Moodle configuration settings. Restrict database and backup access appropriately.';
$string['setting_drm_enabled']        = 'Enable DRM';
$string['setting_drm_enabled_desc']   = 'Enable DRM-protected playback for supported uploads. Requires a valid <strong>DRM Configuration ID</strong> below.';
$string['setting_drm_config_id']      = 'DRM Configuration ID';
$string['setting_drm_config_id_desc'] = 'The DRM configuration ID from your FastPix Dashboard. <strong>Required when DRM is enabled.</strong>';
$string['setting_webhook_url']        = 'Webhook URL';
$string['setting_webhook_url_help']      = 'Paste this URL into the {$a} to start receiving events. Moodle verifies the signature against the secret below.';
$string['setting_webhook_url_help_link'] = 'FastPix Dashboard → Webhooks';
$string['button_copy_webhook_url']      = 'Copy';
$string['button_copy_webhook_url_done'] = 'Copied!';

// Privacy API metadata.
$string['privacy:metadata:asset']                       = 'FastPix assets uploaded by the user';
$string['privacy:metadata:asset:owner_userid']          = 'The Moodle user who uploaded the asset';
$string['privacy:metadata:asset:fastpix_id']            = 'The FastPix-side identifier of the asset';
$string['privacy:metadata:asset:title']                 = 'The title of the asset';
$string['privacy:metadata:asset:duration']              = 'The duration in seconds';
$string['privacy:metadata:asset:timecreated']           = 'When the asset was uploaded';
$string['privacy:metadata:upload_session']              = 'In-progress FastPix upload sessions';
$string['privacy:metadata:upload_session:userid']       = 'The Moodle user who started the upload';
$string['privacy:metadata:upload_session:upload_id']    = 'The FastPix-side upload session identifier';
$string['privacy:metadata:upload_session:source_url']   = 'The source URL for URL-pull uploads (if applicable)';
$string['privacy:metadata:upload_session:state']        = 'The current state of the upload session';
$string['privacy:metadata:upload_session:timecreated']  = 'When the upload session was started';
$string['privacy:metadata:fastpix']                     = 'FastPix.io — external video hosting service';
$string['privacy:metadata:fastpix:owner_userhash']      = 'An HMAC-derived hash of the user ID (no plaintext PII sent)';
$string['privacy:metadata:fastpix:site_url']            = 'The Moodle site URL (used for cross-asset audit)';
// V1.0 cleanup — new strings.
$string['task_purge_soft_deleted_assets'] = 'Hard-purge soft-deleted assets after 7 days';
$string['event_webhook_secret_rotated']   = 'Webhook signing secret rotated';

$string['asset_not_ready'] = 'Asset exists but is not yet ready for playback: {$a}';

$string['settings_credentials']             = 'API credentials';
$string['settings_credentials_desc']        = 'Used by every server-to-server call to FastPix.';
$string['settings_credentials_link']        = 'Find your keys in the FastPix Dashboard';
$string['ui_btn_copy']                      = 'Copy';
$string['ui_btn_copied']                    = 'Copied';
$string['ui_more_info']                     = 'More information';
$string['ui_toggle_enabled']                = 'Enabled';
$string['ui_toggle_disabled']               = 'Disabled';
$string['settings_features']                = 'Feature flags';
$string['settings_features_desc']           = 'Toggle optional FastPix capabilities. Some require additional configuration in the FastPix Dashboard.';
$string['settings_features_link']           = 'Set up DRM in the FastPix Dashboard';
$string['settings_webhooks']                = 'Webhooks';
$string['settings_webhooks_desc']           = 'FastPix sends event notifications to the webhook URL below for upload, processing, and playback-related updates.';
$string['setting_section_upload_defaults']  = 'Upload defaults';
$string['setting_section_upload_defaults_desc'] = 'Applied to every newly-ingested video.';
$string['setting_section_upload_defaults_link'] = 'Configure playback access &amp; security';
$string['setting_default_access_policy']    = 'Default access policy';
$string['setting_default_access_policy_desc'] = 'Default playback access policy applied to newly uploaded videos.';
$string['setting_max_resolution']           = 'Default maximum resolution';
$string['setting_max_resolution_desc']      = 'Maximum resolution allowed for newly uploaded videos.';
$string['setting_webhook_secret']           = 'Webhook signing secret';
$string['setting_webhook_secret_desc']      = 'Enter the webhook signing secret generated in the FastPix Dashboard.';
$string['setting_webhook_secret_too_short'] = 'Webhook secret must be at least 32 characters; FastPix generates 64-character secrets by default.';
$string['webhook_secret_not_configured_notice'] = 'Webhook signing secret is not configured. FastPix events will be rejected until you paste the secret from the FastPix dashboard below.';

$string['button_test_connection']      = 'Test connection';
$string['button_test_connection_desc'] = 'Verifies that Moodle can authenticate with FastPix using the configured credentials.';
$string['button_send_test_event']      = 'Send test event';
$string['button_send_test_event_desc'] = 'Sends a test webhook event to verify that Moodle can receive and process FastPix events correctly.';
$string['settings_save_first_notice'] = 'Save your changes before using <strong>Test connection</strong> or <strong>Send test event</strong>. Those buttons use the saved settings — not the values currently typed into the fields above.';

$string['test_connection_running'] = 'Probing…';
$string['test_connection_success'] = 'Connected (latency {$a} ms)';
$string['test_connection_failed']  = 'Failed: {$a}';

$string['send_test_event_running'] = 'Sending…';
$string['send_test_event_success'] = 'Test event delivered (ledger id {$a})';
$string['send_test_event_failed']  = 'Failed: {$a}';

// Settings.php — access policy select option labels.
$string['access_policy_public']  = 'Public — playback without authentication';
$string['access_policy_private'] = 'Private — authenticated playback required';
$string['access_policy_drm']     = 'requires DRM configuration and authorized playback';

// Settings.php — rotation status display.
$string['setting_webhook_secret_rotated_at'] = 'Last secret rotation';
