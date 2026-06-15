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
 * Admin settings page for local_fastpix.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of local_fastpix.
//
// Admin settings page for the FastPix integration plugin.
//
// Evaluated by Moodle's admin tree on every admin request. Only the OUTER.
// Settings-page registration runs unconditionally; all widget construction.
// Is gated by `$ADMIN->fulltree` (the admin is actually rendering this.
// Page, not just walking the tree for navigation) AND.
// `Has_capability('local/fastpix:configurecredentials')` so a delegated.
// "credentials manager" role does not need site-config to manage FastPix.
//
// Idempotent + read-only here. No DB writes, no gateway calls — the.
// Settings tree is walked many times per request and a slow path here.
// Would block every admin page render (audit drill 2026-05-11).

defined('MOODLE_INTERNAL') || die();

// This settings page embeds inline SVG icon/logo strings (handed to the AMD
// enhancer as config and rendered through html_writer). Those lines legitimately
// exceed the 132/180-char limits and cannot be wrapped without corrupting the
// SVG markup, so the line-length sniff is disabled file-wide. The admin UI's CSS
// and JS now live in styles.css and amd/src/settings.js respectively.
// phpcs:disable moodle.Files.LineLength.MaxExceeded, moodle.Files.LineLength.TooLong

if (!$hassiteconfig) {
    return;
}

$settings = new admin_settingpage(
    'local_fastpix',
    new lang_string('pluginname', 'local_fastpix'),
);
$ADMIN->add('server', $settings);

if (!$ADMIN->fulltree) {
    return;
}

if (!has_capability('local/fastpix:configurecredentials', context_system::instance())) {
    return;
}
// Helper — emit an admin_setting_description that renders a button + a status
// span + a muted descriptor. Centralizes the markup so the two admin buttons
// (Test connection, Send test event) stay byte-identical. The click handler is
// wired by the local_fastpix/settings AMD module (loaded at the foot of this
// file); the AJAX call itself runs through core/ajax. No inline <script>.
//
// Parameters: $buttonid, $statusid, $labelkey, $descriptionkey, and optional
// $iconsvg. Returns the rendered HTML.
$localfastpixbuttonhtml = static function (array $btn): string {
    $buttonid       = $btn['buttonid'];
    $statusid       = $btn['statusid'];
    $labelkey       = $btn['labelkey'];
    $descriptionkey = $btn['descriptionkey'];
    $iconsvg        = $btn['iconsvg'] ?? '';

    // Outlined .fp-ibtn (icon + label), matching the card's Copy buttons.
    $button = \html_writer::tag(
        'button',
        $iconsvg . \html_writer::tag('span', get_string($labelkey, 'local_fastpix')),
        [
            'id'    => $buttonid,
            'type'  => 'button',
            'class' => 'fp-ibtn',
            'title' => get_string($descriptionkey, 'local_fastpix'),
        ]
    );
    $status = \html_writer::tag('span', '', [
        'id'    => $statusid,
        'class' => 'ml-2 ms-2 local-fastpix-status',
    ]);
    $description = \html_writer::tag(
        'div',
        get_string($descriptionkey, 'local_fastpix'),
        ['class' => 'form-text text-muted'],
    );

    return $button . ' ' . $status . $description;
};

// JS config for the AJAX action buttons, accumulated as each button is added
// and handed to the AMD enhancer at the foot of this file. Success/failure
// strings carry {$a} and are resolved JS-side (by string key) to keep them in
// the language pack (rule M4).
$localfastpixjsbuttons = [];

// 1. API credentials.
//
// Section heading carries a short description plus an external link to the
// FastPix "activate your account" docs (where the API keys live). The link
// label and lead-in text are translatable (rule M4); the URL is a docs link,
// not a gateway endpoint, so it stays out of classes/api/ (rule A2 N/A here).

$credentialslink = \html_writer::link(
    'https://fastpix.com/docs/getting-started/activate-your-account',
    get_string('settings_credentials_link', 'local_fastpix') . ' ↗',
    ['target' => '_blank', 'rel' => 'noopener']
);
$credentialsdesc = \html_writer::span(
    get_string('settings_credentials_desc', 'local_fastpix') . ' ' . $credentialslink,
    'fp-cred-desc'
);

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_credentials',
    new lang_string('settings_credentials', 'local_fastpix'),
    $credentialsdesc,
));

// The setting_credential class extends admin_setting_configpasswordunmask, so both the
// API key and secret render masked (browser dots + opt-in reveal) and the value
// is redacted in the admin config-change log. Storage remains plaintext in
// mdl_config_plugins (rule S8, disclosed in README.md). The card enhancer leaves
// these two fields alone (they are intentionally NOT in CFG.secrets below) so
// the native passwordunmask widget owns the mask/reveal affordance.
$settings->add(new \local_fastpix\admin\setting_credential(
    'local_fastpix/apikey',
    new lang_string('setting_apikey', 'local_fastpix'),
    new lang_string('setting_apikey_desc', 'local_fastpix'),
    '',
));

$settings->add(new \local_fastpix\admin\setting_credential(
    'local_fastpix/apisecret',
    new lang_string('setting_apisecret', 'local_fastpix'),
    new lang_string('setting_apisecret_desc', 'local_fastpix'),
    '',
));

$btntestconnectionid = 'local_fastpix_test_connection_btn';
$btntestconnectionstatusid = 'local_fastpix_test_connection_status';
$settings->add(new admin_setting_description(
    'local_fastpix/test_connection_button',
    new lang_string('button_test_connection', 'local_fastpix'),
    $localfastpixbuttonhtml([
        'buttonid'       => $btntestconnectionid,
        'statusid'       => $btntestconnectionstatusid,
        'labelkey'       => 'button_test_connection',
        'descriptionkey' => 'button_test_connection_desc',
        'iconsvg'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    ]),
));
$localfastpixjsbuttons[] = [
    'buttonId'     => $btntestconnectionid,
    'statusId'     => $btntestconnectionstatusid,
    'methodname'   => 'local_fastpix_test_connection',
    'successField' => 'latency_ms',
    'runningKey'   => 'test_connection_running',
    'successKey'   => 'test_connection_success',
    'failedKey'    => 'test_connection_failed',
];

// 2. Upload defaults.
//
// Subtitle + docs link, matching the credentials section. No mention of
// teachers (this is a site-admin page); the per-activity override lives in
// mod_fastpix and is documented there, not here.

$uploaddefaultslink = \html_writer::link(
    'https://fastpix.com/docs/upload-videos/upload-videos-from-device',
    get_string('setting_section_upload_defaults_link', 'local_fastpix') . ' ↗',
    ['target' => '_blank', 'rel' => 'noopener']
);
$uploaddefaultsdesc = get_string('setting_section_upload_defaults_desc', 'local_fastpix') . ' ' . $uploaddefaultslink;

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_upload_defaults',
    new lang_string('setting_section_upload_defaults', 'local_fastpix'),
    $uploaddefaultsdesc,
));

$settings->add(new admin_setting_configselect(
    'local_fastpix/default_access_policy',
    new lang_string('setting_default_access_policy', 'local_fastpix'),
    new lang_string('setting_default_access_policy_desc', 'local_fastpix'),
    'private',
    [
        'public'  => new lang_string('access_policy_public', 'local_fastpix'),
        'private' => new lang_string('access_policy_private', 'local_fastpix'),
        'drm'     => new lang_string('access_policy_drm', 'local_fastpix'),
    ],
));

$settings->add(new admin_setting_configselect(
    'local_fastpix/max_resolution',
    new lang_string('setting_max_resolution', 'local_fastpix'),
    new lang_string('setting_max_resolution_desc', 'local_fastpix'),
    '1080p',
    [
        '480p'  => '480p',
        '720p'  => '720p',
        '1080p' => '1080p',
        '1440p' => '1440p',
        '2160p' => '2160p',
    ],
));

$settings->add(new admin_setting_configcheckbox(
    'local_fastpix/enablehealthendpoint',
    new lang_string('setting_enablehealthendpoint', 'local_fastpix'),
    new lang_string('setting_enablehealthendpoint_desc', 'local_fastpix'),
    1,
));

// 3. Retention & cleanup.
//
// Lifecycle windows driving the orphan sweeper and the unattached-asset
// releaser tasks. The three duration values are read by the scheduled tasks;
// auto_release_enabled is the master switch that turns notify-only into
// actually-delete. The warning lead must stay shorter than the grace period
// (the description says so; the releaser task is the runtime guard).

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_retention',
    new lang_string('settings_retention', 'local_fastpix'),
    new lang_string('settings_retention_desc', 'local_fastpix'),
));

$settings->add(new admin_setting_configduration(
    'local_fastpix/orphaned_session_ttl',
    new lang_string('setting_orphaned_session_ttl', 'local_fastpix'),
    new lang_string('setting_orphaned_session_ttl_desc', 'local_fastpix'),
    DAYSECS,
));

$settings->add(new admin_setting_configduration(
    'local_fastpix/unattached_grace',
    new lang_string('setting_unattached_grace', 'local_fastpix'),
    new lang_string('setting_unattached_grace_desc', 'local_fastpix'),
    WEEKSECS,
));

$settings->add(new admin_setting_configduration(
    'local_fastpix/unattached_warning_lead',
    new lang_string('setting_unattached_warning_lead', 'local_fastpix'),
    new lang_string('setting_unattached_warning_lead_desc', 'local_fastpix'),
    2 * DAYSECS,
));

$settings->add(new admin_setting_configcheckbox(
    'local_fastpix/auto_release_enabled',
    new lang_string('setting_auto_release_enabled', 'local_fastpix'),
    new lang_string('setting_auto_release_enabled_desc', 'local_fastpix'),
    0,
));

// 4. Feature flags.
//
// Subtitle + docs link, matching the credentials / upload-defaults sections.
// The link points at the DRM-encryption setup docs (where the DRM
// Configuration ID is created); it's a docs URL, not a gateway endpoint, so
// it stays out of classes/api/ (rule A2 N/A here).

$featureslink = \html_writer::link(
    'https://fastpix.com/docs/video-security/set-up-drm-encryption',
    get_string('settings_features_link', 'local_fastpix') . ' ↗',
    ['target' => '_blank', 'rel' => 'noopener']
);
$featuresdesc = get_string('settings_features_desc', 'local_fastpix') . ' ' . $featureslink;

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_features',
    new lang_string('settings_features', 'local_fastpix'),
    $featuresdesc,
));

$settings->add(new admin_setting_configcheckbox(
    'local_fastpix/feature_drm_enabled',
    new lang_string('setting_drm_enabled', 'local_fastpix'),
    new lang_string('setting_drm_enabled_desc', 'local_fastpix'),
    0,
));

$settings->add(new admin_setting_configtext(
    'local_fastpix/drm_configuration_id',
    new lang_string('setting_drm_config_id', 'local_fastpix'),
    new lang_string('setting_drm_config_id_desc', 'local_fastpix'),
    '',
    PARAM_RAW_TRIMMED,
));

// Show/hide the DRM config id with the DRM toggle is handled by the card
// enhancer's toggle logic (amd/src/settings.js, CFG.reveals), which owns the
// pill toggle and therefore the dependent-field visibility deterministically.
// The runtime double-gate (rule W12) is what actually enforces correctness;
// this is UI clarity only. With JS off the field simply stays visible.

// 5. Webhooks.
//
// No docs link in the heading — the inline help below the Webhook URL field
// already links to the FastPix Dashboard → Webhooks docs.

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_webhooks',
    new lang_string('settings_webhooks', 'local_fastpix'),
    new lang_string('settings_webhooks_desc', 'local_fastpix'),
));

// Conditional "not configured" notice — only when the secret is empty so.
// The warning disappears on first paste.
if (trim((string)get_config('local_fastpix', 'webhook_secret_current')) === '') {
    $settings->add(new admin_setting_description(
        'local_fastpix/webhook_secret_not_configured_notice',
        '',
        \html_writer::div(
            get_string('webhook_secret_not_configured_notice', 'local_fastpix'),
            'alert alert-warning',
        ),
    ));
}

$webhookurl = (new moodle_url('/local/fastpix/webhook.php'))->out(false);
$webhookurlid = 'local_fastpix_webhook_url_value';
$webhookurlbtnid = 'local_fastpix_webhook_url_copy_btn';
// Copy/check icons inlined here (the shared $fprowicons array is defined
// further down, after this block) so the webhook Copy button matches the
// secret rows' icon + label treatment.
$webhookcopyicon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>';
$webhookcheckicon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg>';
// Copy-button config handed to the local_fastpix/settings AMD enhancer.
$localfastpixjscopy = [
    'urlId'     => $webhookurlid,
    'btnId'     => $webhookurlbtnid,
    'iconCopy'  => $webhookcopyicon,
    'iconCheck' => $webhookcheckicon,
    'labelCopy' => get_string('button_copy_webhook_url', 'local_fastpix'),
    'labelDone' => get_string('button_copy_webhook_url_done', 'local_fastpix'),
];
// Reuse the card's .fp-input-wrap layout (same as the secret rows) so the
// read-only URL input shrinks (min-width:0) and the Copy button stays inside
// the card instead of overflowing on long ngrok/site URLs.
$webhookurlhtml = \html_writer::start_tag('div', ['class' => 'fp-input-wrap']);
$webhookurlhtml .= \html_writer::empty_tag('input', [
    'type'     => 'text',
    'id'       => $webhookurlid,
    'value'    => $webhookurl,
    'readonly' => 'readonly',
    'onclick'  => 'this.select();',
]);
$webhookurlhtml .= \html_writer::tag(
    'button',
    $webhookcopyicon . '<span>' . get_string('button_copy_webhook_url', 'local_fastpix') . '</span>',
    [
        'id'    => $webhookurlbtnid,
        'type'  => 'button',
        'class' => 'fp-ibtn',
    ]
);
$webhookurlhtml .= \html_writer::end_tag('div');
// Help text below the input. "FastPix Dashboard → Webhooks" is linked to the
// webhooks docs; the surrounding sentence is translatable ({$a} = the link).
$webhookurlhelplink = \html_writer::link(
    'https://fastpix.com/docs/webhooks/set-up-webhooks',
    get_string('setting_webhook_url_help_link', 'local_fastpix'),
    ['target' => '_blank', 'rel' => 'noopener']
);
$webhookurlhtml .= \html_writer::div(
    get_string('setting_webhook_url_help', 'local_fastpix', $webhookurlhelplink),
    'fp-field-help'
);
$settings->add(new admin_setting_description(
    'local_fastpix/webhook_url',
    new lang_string('setting_webhook_url', 'local_fastpix'),
    $webhookurlhtml,
));

$settings->add(new \local_fastpix\admin\setting_webhook_secret(
    'local_fastpix/webhook_secret_current',
    new lang_string('setting_webhook_secret', 'local_fastpix'),
    new lang_string('setting_webhook_secret_desc', 'local_fastpix'),
    '',
    PARAM_RAW_TRIMMED,
    64,
));

// Last-rotation timestamp display (read-only operator hint). Only shown.
// When a rotation has actually occurred. Format via userdate so it.
// Respects the operator's timezone / locale.
$rotatedat = (int)get_config('local_fastpix', 'webhook_secret_rotated_at');
if ($rotatedat > 0) {
    $settings->add(new admin_setting_description(
        'local_fastpix/webhook_secret_rotated_at_display',
        new lang_string('setting_webhook_secret_rotated_at', 'local_fastpix'),
        \html_writer::tag('code', s(userdate($rotatedat))),
    ));
}

$btnsendeventid = 'local_fastpix_send_test_event_btn';
$btnsendeventstatusid = 'local_fastpix_send_test_event_status';
$settings->add(new admin_setting_description(
    'local_fastpix/send_test_event_button',
    new lang_string('button_send_test_event', 'local_fastpix'),
    $localfastpixbuttonhtml([
        'buttonid'       => $btnsendeventid,
        'statusid'       => $btnsendeventstatusid,
        'labelkey'       => 'button_send_test_event',
        'descriptionkey' => 'button_send_test_event_desc',
        'iconsvg'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
    ]),
));
$localfastpixjsbuttons[] = [
    'buttonId'     => $btnsendeventid,
    'statusId'     => $btnsendeventstatusid,
    'methodname'   => 'local_fastpix_send_test_event',
    'successField' => 'ledger_id',
    'runningKey'   => 'send_test_event_running',
    'successKey'   => 'send_test_event_success',
    'failedKey'    => 'send_test_event_failed',
];

// Card restyle (progressive enhancement).
//
// Groups each section (an <h3 class="main"> plus the settings that follow it)
// into a bordered card with an icon header, and decorates the credential rows
// with masked inputs + reveal/copy. Pure client-side: with JS off the page
// renders and saves through Moodle's default widgets. CSS is inlined here so
// it applies on a normal refresh without a theme cache purge. Section icons
// are matched to headings by their (translated) title text.

$fpcardicons = [
    'key'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="M10.85 12.15L19 4M18 5l3 3M15 8l3 3"/></svg>',
    'upload' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 16l-4-4-4 4M12 12v9M20.4 14.5A5 5 0 0017 6h-1.3A8 8 0 104 15.3"/></svg>',
    'trash'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
    'flag'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V3M4 4h14l-3 5 3 5H4"/></svg>',
    'hook'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 007.07 0l3-3a5 5 0 00-7.07-7.07l-1 1"/><path d="M14 11a5 5 0 00-7.07 0l-3 3a5 5 0 007.07 7.07l1-1"/></svg>',
];
$fprowicons = [
    'eye'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>',
    'eyeoff' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 5.1A11 11 0 0112 5c7 0 11 7 11 7a18 18 0 01-3.2 4M6.6 6.6A18 18 0 001 12s4 7 11 7c1.7 0 3.3-.4 4.7-1.1M14.1 14.1A3 3 0 119.9 9.9M1 1l22 22"/></svg>',
    'copy'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>',
    'check'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg>',
    'chevron' => '<svg viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"/></svg>',
];

// FastPix logo, inlined as SVG (same approach as the section icons). The card
// enhancer drops it beside the core "FastPix" page heading (brandName).
$fpbrandlogo = '<svg viewBox="0 0 494 433" fill="none" aria-hidden="true">'
    . '<path fill-rule="evenodd" clip-rule="evenodd" d="M41.9325 1.64899C41.9325 1.64899 10.5897 1.09241 1.33147 33.2814C1.33147 33.2814 -3.29762 53.5038 4.70686 67.3256L273.58 103.874C273.58 103.874 289.204 104.524 287.95 122.52C287.95 122.52 285.539 136.434 271.844 136.342L53.6982 150.163L146.666 306.748L246.481 203.688C246.481 203.688 254.485 193.577 269.337 201.276C269.337 201.276 281.199 210.367 274.448 222.333L181.384 365.097L208.484 413.797C208.484 413.797 223.143 433.834 248.603 432.443C248.603 432.443 274.159 432.814 289.686 406.469L488.351 65.7486C488.351 65.7486 503.396 35.9715 483.24 15.4708C483.24 15.4708 470.028 -1.22668 446.014 0.0720112L41.9325 1.64899Z" fill="#14CC80"/>'
    . '<path d="M97.767 224.282L475.617 77.7159C475.617 77.7159 493.748 68.6251 493.748 37.5493C493.748 37.5493 493.748 10.0913 458.548 1.00049L41.9285 1.64983C41.9285 1.64983 -12.1406 5.15676 5.31496 66.659L279.17 105.127C279.17 105.127 300.001 111.482 279.17 136.064L53.6941 150.164L97.767 224.282Z" fill="#30F2A2"/>'
    . '</svg>';

$fpcardcfg = [
    'brandName' => get_string('pluginname', 'local_fastpix'),
    'brandLogo' => $fpbrandlogo,
    // Shown in a popup from the "i" button beside the Test connection / Send
    // test event row labels (CFG.infoRows = their admin form-item ids).
    'saveNotice' => get_string('settings_save_first_notice', 'local_fastpix'),
    'infoLabel'  => get_string('ui_more_info', 'local_fastpix'),
    'infoIcon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    // Button ids (admin_setting_description rows have no admin-<name> id, so we
    // anchor off the button and walk up to its row label).
    'infoRows'   => ['local_fastpix_test_connection_btn', 'local_fastpix_send_test_event_btn'],
    'sections' => [
        ['title' => get_string('settings_credentials', 'local_fastpix'), 'icon' => $fpcardicons['key']],
        ['title' => get_string('setting_section_upload_defaults', 'local_fastpix'), 'icon' => $fpcardicons['upload']],
        ['title' => get_string('settings_retention', 'local_fastpix'), 'icon' => $fpcardicons['trash']],
        ['title' => get_string('settings_features', 'local_fastpix'), 'icon' => $fpcardicons['flag']],
        ['title' => get_string('settings_webhooks', 'local_fastpix'), 'icon' => $fpcardicons['hook']],
    ],
    // The apikey/apisecret fields are NOT listed here: they use admin_setting_configpasswordunmask,
    // which owns its own mask/reveal widget. The card enhancer only decorates the
    // remaining plain-text secret-ish fields.
    'secrets'     => [
        'id_s_local_fastpix_drm_configuration_id',
        'id_s_local_fastpix_webhook_secret_current',
    ],
    'icons'       => $fprowicons,
    // Checkboxes to re-render as pill toggles. Native input stays in the DOM
    // (visually hidden) as the form's source of truth — Moodle's save path is
    // untouched, and with JS off the page still renders a working checkbox.
    'toggles'     => [
        'id_s_local_fastpix_feature_drm_enabled',
    ],
    // Toggle input id => admin form-item id to show only when the toggle is on.
    'reveals'     => [
        'id_s_local_fastpix_feature_drm_enabled' => 'admin-drm_configuration_id',
    ],
    'labels'      => [
        'copy'      => get_string('ui_btn_copy', 'local_fastpix'),
        'copied'    => get_string('ui_btn_copied', 'local_fastpix'),
        'toggleOn'  => get_string('ui_toggle_enabled', 'local_fastpix'),
        'toggleOff' => get_string('ui_toggle_disabled', 'local_fastpix'),
        // Used to rewrite the native checkbox "Default: No/Yes" hint to the
        // Enabled/Disabled wording shown next to the toggle.
        'defaultNo'  => get_string('no'),
        'defaultYes' => get_string('yes'),
    ],
];

// Load the settings-page enhancer. All inline <script>/<style> that used to
// live in this file has moved out per the Moodle coding style — the JS into
// amd/src/settings.js, the CSS into styles.css. Every enhancement (section
// cards, masked credential inputs, pill toggles, custom selects, the AJAX
// action buttons and the webhook-URL copy) degrades gracefully: with JS off
// the page still renders and saves through Moodle's default widgets.
// Only queue the browser enhancer when actually rendering the page. During CLI
// (install/upgrade/cron) Moodle builds this settings tree with fulltree=true to
// apply default settings; requiring page JS there is pointless and can run
// before $PAGE is ready, so skip it.
if (!CLI_SCRIPT) {
    global $PAGE;
    $PAGE->requires->js_call_amd('local_fastpix/settings', 'init', [[
        'buttons' => $localfastpixjsbuttons,
        'copy'    => $localfastpixjscopy,
        'cards'   => $fpcardcfg,
    ]]);
}
