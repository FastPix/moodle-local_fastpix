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
// Helper — emit an admin_setting_description that renders a button + a.
// Status span + a muted descriptor. Centralizes the markup so the two.
// Admin buttons (Test connection, Send test event) stay byte-identical.

// Build the HTML for an inline admin button + status pair + an inline.
// Script tag that wires a click handler. Uses native fetch() against.
// /Lib/ajax/service.php (the same endpoint Moodle's core/ajax AMD module.
// Uses) so we don't depend on the AMD loader — which has been unreliable.
// Enough on this dev stack to break sibling admin widgets.
//
// Parameters: $buttonid, $statusid, $labelkey, $descriptionkey,.
// $methodname, $successtpl, $successfield. Returns the rendered HTML.
$localfastpixbuttonhtml = static function (
    string $buttonid,
    string $statusid,
    string $labelkey,
    string $descriptionkey,
    string $methodname,
    string $successtpl,
    string $successfield,
    string $iconsvg = '',
): string {
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

    // Inline <script> binding. Reads sesskey from M.cfg.sesskey (always.
    // Available on admin pages). All JSON encoding via PHP-side.
    // Json_encode so we don't smuggle user input into JS.
    $args = [
        'buttonId'     => $buttonid,
        'statusId'     => $statusid,
        'methodname'   => $methodname,
        'successTpl'   => $successtpl,
        'successField' => $successfield,
    ];
    $argsjson = json_encode($args, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
    $script = <<<SCRIPT
<script>
(function() {
    var cfg = {$argsjson};
    function bind() {
        var btn = document.getElementById(cfg.buttonId);
        var status = document.getElementById(cfg.statusId);
        if (!btn || !status || btn.dataset.fpBound === '1') return;
        btn.dataset.fpBound = '1';
        btn.addEventListener('click', function() {
            function setState(cls, text) {
                status.classList.remove('fp-status-busy', 'fp-status-ok', 'fp-status-err');
                if (cls) { status.classList.add(cls); }
                status.textContent = text;
            }
            function ok(text) { setState('fp-status-ok', '✓ ' + text); }
            function fail(text) { setState('fp-status-err', '✕ ' + text); }
            setState('fp-status-busy', 'Working…');
            btn.disabled = true;
            var payload = [{index: 0, methodname: cfg.methodname, args: {}}];
            var sesskey = (window.M && window.M.cfg && window.M.cfg.sesskey) || '';
            var url = M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' + encodeURIComponent(sesskey)
                    + '&info=' + encodeURIComponent(cfg.methodname);
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); })
              .then(function(rs) {
                  btn.disabled = false;
                  var r = rs && rs[0];
                  if (r && r.error) {
                      fail(r.exception ? r.exception.message : r.error);
                      return;
                  }
                  var data = r && r.data;
                  if (data && data.success) {
                      ok(cfg.successTpl.replace('{\$a}', String(data[cfg.successField])));
                  } else {
                      var msg = (data && (data.error || (data.errors && data.errors.join(', ')) || data.result)) || 'unknown';
                      fail(msg);
                  }
              }).catch(function(err) {
                  btn.disabled = false;
                  fail((err && err.message) || err);
              });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
SCRIPT;

    return $button . ' ' . $status . $description . $script;
};

// 1. API credentials.
//
// Section heading carries a short description plus an external link to the
// FastPix "activate your account" docs (where the API keys live). The link
// label and lead-in text are translatable (rule M4); the URL is a docs link,
// not a gateway endpoint, so it stays out of classes/api/ (rule A2 N/A here).

$credentialsdesc = \html_writer::span(
    get_string('settings_credentials_desc', 'local_fastpix') . ' '
    . \html_writer::link(
        'https://docs.fastpix.io/docs/authentication-with-access-tokens',
        get_string('settings_credentials_link', 'local_fastpix') . ' ↗',
        ['target' => '_blank', 'rel' => 'noopener']),
    'fp-cred-desc');

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_credentials',
    new lang_string('settings_credentials', 'local_fastpix'),
    $credentialsdesc,
));

$settings->add(new \local_fastpix\admin\setting_credential(
    'local_fastpix/apikey',
    new lang_string('setting_apikey', 'local_fastpix'),
    new lang_string('setting_apikey_desc', 'local_fastpix'),
    '',
    PARAM_RAW_TRIMMED,
));

// Plain text input instead of admin_setting_configpasswordunmask. The.
// Passwordunmask widget depends on the core_admin/show_unmask_password.
// AMD module to bind its "click to edit" affordance; in our dev stack.
// That JS chain is intermittently broken, leaving the field inert.
// The secret is stored as plaintext in mdl_config_plugins regardless.
// Of the widget (rule S8 — already disclosed in README.md), so the.
// Visual mask was cosmetic. The text input is always editable.
$settings->add(new \local_fastpix\admin\setting_credential(
    'local_fastpix/apisecret',
    new lang_string('setting_apisecret', 'local_fastpix'),
    new lang_string('setting_apisecret_desc', 'local_fastpix'),
    '',
    PARAM_RAW_TRIMMED,
));

$btntestconnectionid = 'local_fastpix_test_connection_btn';
$btntestconnectionstatusid = 'local_fastpix_test_connection_status';
$settings->add(new admin_setting_description(
    'local_fastpix/test_connection_button',
    new lang_string('button_test_connection', 'local_fastpix'),
    $localfastpixbuttonhtml(
        $btntestconnectionid,
        $btntestconnectionstatusid,
        'button_test_connection',
        'button_test_connection_desc',
        'local_fastpix_test_connection',
        'Authenticated · {$a} ms',
        'latency_ms',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    ),
));

// 2. Upload defaults.
//
// Subtitle + docs link, matching the credentials section. No mention of
// teachers (this is a site-admin page); the per-activity override lives in
// mod_fastpix and is documented there, not here.

$uploaddefaultsdesc = get_string('setting_section_upload_defaults_desc', 'local_fastpix') . ' '
    . \html_writer::link(
        'https://docs.fastpix.io/docs/enable-drm-protection',
        get_string('setting_section_upload_defaults_link', 'local_fastpix') . ' ↗',
        ['target' => '_blank', 'rel' => 'noopener']);

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

// 3. Feature flags.
//
// Subtitle + docs link, matching the credentials / upload-defaults sections.
// The link points at the DRM-encryption setup docs (where the DRM
// Configuration ID is created); it's a docs URL, not a gateway endpoint, so
// it stays out of classes/api/ (rule A2 N/A here).

$featuresdesc = get_string('settings_features_desc', 'local_fastpix') . ' '
    . \html_writer::link(
        'https://docs.fastpix.io/docs/enable-drm-protection',
        get_string('settings_features_link', 'local_fastpix') . ' ↗',
        ['target' => '_blank', 'rel' => 'noopener']);

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
// enhancer's toggle logic (see $fpcardscript: CFG.reveals), which owns the
// pill toggle and therefore the dependent-field visibility deterministically.
// The runtime double-gate (rule W12) is what actually enforces correctness;
// this is UI clarity only. With JS off the field simply stays visible.

// 4. Webhooks.
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
$webhookurljson = json_encode([
    'urlId'     => $webhookurlid,
    'btnId'     => $webhookurlbtnid,
    'iconCopy'  => $webhookcopyicon,
    'iconCheck' => $webhookcheckicon,
    'labelCopy' => get_string('button_copy_webhook_url', 'local_fastpix'),
    'labelDone' => get_string('button_copy_webhook_url_done', 'local_fastpix'),
], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
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
    'https://docs.fastpix.io/docs/webhooks-for-status',
    get_string('setting_webhook_url_help_link', 'local_fastpix'),
    ['target' => '_blank', 'rel' => 'noopener']);
$webhookurlhtml .= \html_writer::div(
    get_string('setting_webhook_url_help', 'local_fastpix', $webhookurlhelplink),
    'fp-field-help');
$webhookurlhtml .= <<<SCRIPT
<script>
(function() {
    var cfg = {$webhookurljson};
    function bind() {
        var btn = document.getElementById(cfg.btnId);
        var urlEl = document.getElementById(cfg.urlId);
        if (!btn || !urlEl || btn.dataset.fpBound === '1') return;
        btn.dataset.fpBound = '1';
        btn.addEventListener('click', function() {
            var text = (urlEl.value !== undefined ? urlEl.value : urlEl.textContent) || '';
            var done = function() {
                btn.innerHTML = cfg.iconCheck + '<span>' + cfg.labelDone + '</span>';
                setTimeout(function() {
                    btn.innerHTML = cfg.iconCopy + '<span>' + cfg.labelCopy + '</span>';
                }, 1500);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(function() {
                    // Fallback for older browsers / non-secure contexts.
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); done(); } finally { document.body.removeChild(ta); }
                });
            } else {
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); done(); } finally { document.body.removeChild(ta); }
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
SCRIPT;
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
    $localfastpixbuttonhtml(
        $btnsendeventid,
        $btnsendeventstatusid,
        'button_send_test_event',
        'button_send_test_event_desc',
        'local_fastpix_send_test_event',
        'Test event delivered (ledger id {$a})',
        'ledger_id',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
    ),
));

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

$fpcardcfg = json_encode([
    'brandName' => get_string('pluginname', 'local_fastpix'),
    'brandLogo' => $fpbrandlogo,
    'sections' => [
        ['title' => get_string('settings_credentials', 'local_fastpix'), 'icon' => $fpcardicons['key']],
        ['title' => get_string('setting_section_upload_defaults', 'local_fastpix'), 'icon' => $fpcardicons['upload']],
        ['title' => get_string('settings_features', 'local_fastpix'), 'icon' => $fpcardicons['flag']],
        ['title' => get_string('settings_webhooks', 'local_fastpix'), 'icon' => $fpcardicons['hook']],
    ],
    'skipIds'     => ['admin-fp_cards'],
    'secrets'     => [
        'id_s_local_fastpix_apikey',
        'id_s_local_fastpix_apisecret',
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
], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);

$fpcardstyle = <<<'CSS'
<style>
/* NB: no overflow:hidden on the card — it would clip the custom <select>
   dropdown menu (position:absolute) and kill its scroll. Header corners are
   rounded explicitly instead so the header background stays inside the
   rounded card border. */
#page-admin-setting-local_fastpix .fp-card{background:#fff;border:1px solid #ececef;border-radius:12px;margin:0 0 18px;}
#page-admin-setting-local_fastpix .fp-card-h{display:flex;gap:14px;align-items:center;padding:18px 24px;background:#fafbfc;border-bottom:1px solid #ececef;border-radius:12px 12px 0 0;}
#page-admin-setting-local_fastpix .fp-card-icon{width:46px;height:46px;border-radius:10px;background:#fef0f5;color:#ec1e5b;display:grid;place-items:center;flex-shrink:0;}
#page-admin-setting-local_fastpix .fp-card-icon svg{width:26px;height:26px;}
#page-admin-setting-local_fastpix .fp-card-htext{flex:1;min-width:0;}
#page-admin-setting-local_fastpix .fp-card-htext h3.main{margin:0;padding:0;border:0;font-size:20px;font-weight:800;color:#1d2125;letter-spacing:-.01em;}
#page-admin-setting-local_fastpix .fp-card-htext .formsettingheading{border:0;background:none;padding:0;margin:4px 0 0;font-size:14px;color:#4f5560;line-height:1.5;}
#page-admin-setting-local_fastpix .fp-card-htext .formsettingheading a{color:#0f6cbf;font-weight:600;text-decoration:none;}
#page-admin-setting-local_fastpix .fp-card-htext .formsettingheading a:hover{color:#0a5499;text-decoration:underline;}
#page-admin-setting-local_fastpix .fp-cred-desc{white-space:nowrap;}
#page-admin-setting-local_fastpix .fp-card-body .form-item{display:grid;grid-template-columns:240px 1fr;gap:24px;padding:20px 24px;margin:0;border-bottom:1px solid #ececef;align-items:flex-start;}
#page-admin-setting-local_fastpix .fp-card-body .form-item:last-child{border-bottom:0;}
/* text-align:left !important beats Bootstrap's .text-sm-end (text-align:right
   !important) on the admin_setting_description label (Test connection / Send
   test event rows), which otherwise right-aligns the title against the button. */
#page-admin-setting-local_fastpix .fp-card-body .form-label{float:none;width:auto;max-width:none;text-align:left!important;padding:0;font-size:15px;font-weight:600;color:#1d2125;}
#page-admin-setting-local_fastpix .fp-card-body .form-label .form-shortname{display:block;font-size:12px;color:#767b85;margin-top:4px;font-family:'JetBrains Mono',ui-monospace,Menlo,monospace;font-weight:400;}
#page-admin-setting-local_fastpix .fp-card-body .form-setting{float:none;width:auto;max-width:none;margin:0;padding:0;}
#page-admin-setting-local_fastpix .fp-card-body .form-description{float:none;max-width:64ch;margin:10px 0 0;padding:0;grid-column:2;font-size:14px;color:#4f5560;line-height:1.55;}
#page-admin-setting-local_fastpix .fp-card-body .form-defaultinfo{font-size:14px;color:#767b85;margin-top:10px;}
#page-admin-setting-local_fastpix .fp-card-body .form-description code,#page-admin-setting-local_fastpix .fp-card-body .form-defaultinfo code{background:#f6f7f9;padding:1px 6px;border-radius:4px;font-size:.92em;}
#page-admin-setting-local_fastpix .fp-input-wrap{display:flex;align-items:stretch;gap:8px;max-width:640px;}
#page-admin-setting-local_fastpix .fp-card-body .form-item input[type=text]{flex:1;min-width:0;height:44px;padding:0 14px;border:1px solid #dee2e6;border-radius:8px;font-family:'JetBrains Mono',ui-monospace,Menlo,monospace;font-size:14px;color:#1d2125;background:#fff;}
#page-admin-setting-local_fastpix .fp-card-body .form-item input[type=text]:focus{outline:0;border-color:#ec1e5b;box-shadow:0 0 0 3px rgba(236,30,91,.12);}
#page-admin-setting-local_fastpix .fp-card-body .form-item select{height:44px;width:100%;max-width:640px;padding:0 38px 0 14px;border:1px solid #dee2e6;border-radius:8px;background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' stroke='%23767b85' stroke-width='1.5' fill='none' stroke-linecap='round'/></svg>") no-repeat right 14px center;appearance:none;-webkit-appearance:none;font-size:14px;color:#1d2125;cursor:pointer;}
#page-admin-setting-local_fastpix .fp-card-body .form-item select:focus{outline:0;border-color:#ec1e5b;box-shadow:0 0 0 3px rgba(236,30,91,.12);}
/* Button rows (Test connection / Send test event): line 1 is the button plus,
   on click, a green/red result chip; the static hint sits below as a blue info
   chip (always shown). */
#page-admin-setting-local_fastpix .form-description .form-text{display:table;margin:14px 0 0;padding:10px 14px;border:0;border-radius:8px;background:#e6f0fa;color:#0f6cbf;font-family:'JetBrains Mono',ui-monospace,Menlo,monospace;font-size:13.5px;line-height:1.6;white-space:nowrap;}
#page-admin-setting-local_fastpix .fp-card-body .form-item:has(.form-description .btn) .form-setting{padding-left:16px;}
#page-admin-setting-local_fastpix .form-description .btn{vertical-align:middle;}
#page-admin-setting-local_fastpix .local-fastpix-status{display:inline-flex;align-items:center;gap:8px;margin-left:14px;vertical-align:middle;font-family:'JetBrains Mono',ui-monospace,Menlo,monospace;font-size:13.5px;line-height:1;}
#page-admin-setting-local_fastpix .local-fastpix-status:empty{display:none;}
#page-admin-setting-local_fastpix .local-fastpix-status.fp-status-ok{padding:9px 14px;border-radius:8px;background:#e6f5ec;color:#138a3f;}
#page-admin-setting-local_fastpix .local-fastpix-status.fp-status-err{padding:9px 14px;border-radius:8px;background:#fdecec;color:#c92a2a;}
#page-admin-setting-local_fastpix .local-fastpix-status.fp-status-busy{color:#767b85;}
/* Custom accessible select (replaces native dropdown UI; native select stays in DOM, hidden, for form submit). */
#page-admin-setting-local_fastpix .fp-select-wrap{position:relative;max-width:640px;}
#page-admin-setting-local_fastpix .fp-native-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;border:0;overflow:hidden;clip:rect(0,0,0,0);}
#page-admin-setting-local_fastpix .fp-select{display:flex;align-items:center;gap:10px;width:100%;height:44px;padding:0 14px;border:1px solid #dee2e6;border-radius:8px;background:#fff;font-size:14px;font-family:inherit;color:#1d2125;cursor:pointer;text-align:left;}
#page-admin-setting-local_fastpix .fp-select:hover{border-color:#c9cfd8;}
#page-admin-setting-local_fastpix .fp-select-wrap.is-open .fp-select,#page-admin-setting-local_fastpix .fp-select:focus-visible{outline:0;border-color:#ec1e5b;box-shadow:0 0 0 3px rgba(236,30,91,.12);}
#page-admin-setting-local_fastpix .fp-select-label{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
#page-admin-setting-local_fastpix .fp-select-chev{flex-shrink:0;color:#767b85;display:grid;place-items:center;transition:transform .18s ease;}
#page-admin-setting-local_fastpix .fp-select-chev svg{width:12px;height:8px;}
#page-admin-setting-local_fastpix .fp-select-wrap.is-open .fp-select-chev{transform:rotate(180deg);}
#page-admin-setting-local_fastpix .fp-select-menu{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:50;margin:0;padding:6px;list-style:none;background:#fff;border:1px solid #ececef;border-radius:12px;box-shadow:0 12px 32px -8px rgba(20,24,30,.18),0 2px 8px rgba(20,24,30,.06);max-height:320px;overflow:auto;}
#page-admin-setting-local_fastpix .fp-select-opt{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;font-size:14px;color:#1d2125;cursor:pointer;line-height:1.4;}
#page-admin-setting-local_fastpix .fp-select-opt .fp-select-check{width:16px;height:16px;flex-shrink:0;color:#ec1e5b;opacity:0;display:grid;place-items:center;}
#page-admin-setting-local_fastpix .fp-select-opt .fp-select-check svg{width:16px;height:16px;}
#page-admin-setting-local_fastpix .fp-select-opt:hover,#page-admin-setting-local_fastpix .fp-select-opt.is-active{background:#f6f7f9;}
#page-admin-setting-local_fastpix .fp-select-opt.is-selected{color:#c3174a;font-weight:600;}
#page-admin-setting-local_fastpix .fp-select-opt.is-selected .fp-select-check{opacity:1;}
#page-admin-setting-local_fastpix input.fp-masked{-webkit-text-security:disc;letter-spacing:2px;}
/* FastPix logo tile injected beside the core "FastPix" page heading. */
#page-admin-setting-local_fastpix .fp-titled{display:inline-flex;align-items:center;gap:14px;}
#page-admin-setting-local_fastpix .fp-title-logo{display:inline-grid;place-items:center;flex-shrink:0;width:1.7em;height:1.7em;}
#page-admin-setting-local_fastpix .fp-title-logo svg{width:100%;height:100%;display:block;}
/* Inline help text shown below a field's input (e.g. the webhook URL). */
#page-admin-setting-local_fastpix .fp-field-help{margin:12px 0 0;max-width:64ch;font-size:14px;color:#4f5560;line-height:1.55;}
#page-admin-setting-local_fastpix .fp-field-help a{color:#0f6cbf;font-weight:600;text-decoration:none;}
#page-admin-setting-local_fastpix .fp-field-help a:hover{color:#0a5499;text-decoration:underline;}
/* Pill toggle (replaces the native checkbox UI; native input stays hidden in DOM). */
#page-admin-setting-local_fastpix .fp-toggle-wrap{display:inline-flex;align-items:center;gap:14px;}
#page-admin-setting-local_fastpix .fp-toggle{position:relative;flex-shrink:0;width:46px;height:26px;padding:0;border:0;border-radius:999px;background:#d1d5db;cursor:pointer;transition:background .18s ease;}
#page-admin-setting-local_fastpix .fp-toggle::after{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(20,24,30,.3);transition:transform .18s ease;}
#page-admin-setting-local_fastpix .fp-toggle.is-on{background:#ec1e5b;}
#page-admin-setting-local_fastpix .fp-toggle.is-on::after{transform:translateX(20px);}
#page-admin-setting-local_fastpix .fp-toggle:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(236,30,91,.25);}
#page-admin-setting-local_fastpix .fp-toggle-label{font-size:15px;font-weight:600;color:#1d2125;}
#page-admin-setting-local_fastpix .fp-ibtn{appearance:none;cursor:pointer;background:#fff;border:1px solid #dee2e6;border-radius:8px;height:44px;padding:0 14px;font-size:14px;font-weight:600;color:#4f5560;display:inline-flex;align-items:center;gap:7px;}
#page-admin-setting-local_fastpix .fp-ibtn:hover{border-color:#aab2c0;background:#fafbfc;color:#1d2125;}
#page-admin-setting-local_fastpix .fp-ibtn svg{width:20px;height:20px;}
@media (max-width:880px){
#page-admin-setting-local_fastpix .fp-card-body .form-item{grid-template-columns:1fr;gap:8px;}
#page-admin-setting-local_fastpix .fp-card-body .form-description{grid-column:1;}
#page-admin-setting-local_fastpix .fp-cred-desc{white-space:normal;}
}
</style>
CSS;

$fpcardscript = <<<SCRIPT
<script>
(function() {
    var CFG = {$fpcardcfg};
    function ready(fn) {
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); }
        else { fn(); }
    }
    ready(function() {
        try {
            var page = document.getElementById('page-admin-setting-local_fastpix') || document.body;
            var form = document.getElementById('adminsettings') || page;
            var heads = Array.prototype.slice.call(form.querySelectorAll('h3.main'));
            if (!heads.length) { return; }
            var container = heads[0].parentNode;
            page.classList.add('fp-enhanced');

            // Drop the FastPix logo beside the core "FastPix" page-title heading.
            if (CFG.brandLogo && CFG.brandName) {
                var titleHeads = page.querySelectorAll('h1, h2');
                for (var ti = 0; ti < titleHeads.length; ti++) {
                    var th = titleHeads[ti];
                    if (th.dataset.fpLogo === '1' || th.querySelector('svg')) { continue; }
                    if ((th.textContent || '').trim().indexOf(CFG.brandName) === 0) {
                        th.dataset.fpLogo = '1';
                        th.classList.add('fp-titled');
                        var logo = document.createElement('span');
                        logo.className = 'fp-title-logo';
                        logo.innerHTML = CFG.brandLogo;
                        th.insertBefore(logo, th.firstChild);
                        break;
                    }
                }
            }

            var iconByTitle = {};
            CFG.sections.forEach(function(s) { iconByTitle[(s.title || '').trim()] = s.icon; });
            var skip = {};
            (CFG.skipIds || []).forEach(function(id) { skip[id] = 1; });

            // Snapshot the ordered children before we start moving nodes.
            var nodes = Array.prototype.slice.call(container.children);
            var body = null;
            nodes.forEach(function(node) {
                if (node.matches && node.matches('h3.main')) {
                    var card = document.createElement('section');
                    card.className = 'fp-card';
                    var header = document.createElement('div');
                    header.className = 'fp-card-h';
                    var icon = document.createElement('span');
                    icon.className = 'fp-card-icon';
                    icon.innerHTML = iconByTitle[(node.textContent || '').trim()] || '';
                    var htext = document.createElement('div');
                    htext.className = 'fp-card-htext';
                    header.appendChild(icon);
                    header.appendChild(htext);
                    body = document.createElement('div');
                    body.className = 'fp-card-body';
                    card.appendChild(header);
                    card.appendChild(body);
                    container.insertBefore(card, node);
                    htext.appendChild(node);
                    return;
                }
                if (node.classList && node.classList.contains('formsettingheading')) {
                    // Description belongs in the header of the card just built.
                    var card2 = container.querySelector('.fp-card:last-of-type .fp-card-htext');
                    if (card2) { card2.appendChild(node); }
                    return;
                }
                if (body && node.classList && node.classList.contains('form-item')) {
                    if (skip[node.id]) { node.style.display = 'none'; return; }
                    body.appendChild(node);
                }
            });

            // Masked credential inputs with reveal (eye) + copy.
            (CFG.secrets || []).forEach(function(id) {
                var inp = document.getElementById(id);
                if (!inp || inp.dataset.fpDecorated === '1') { return; }
                inp.dataset.fpDecorated = '1';
                inp.classList.add('fp-masked');
                var wrap = document.createElement('div');
                wrap.className = 'fp-input-wrap';
                inp.parentNode.insertBefore(wrap, inp);
                wrap.appendChild(inp);

                var reveal = document.createElement('button');
                reveal.type = 'button';
                reveal.className = 'fp-ibtn';
                reveal.innerHTML = CFG.icons.eye;
                reveal.addEventListener('click', function() {
                    var masked = inp.classList.toggle('fp-masked');
                    reveal.innerHTML = masked ? CFG.icons.eye : CFG.icons.eyeoff;
                });

                var copy = document.createElement('button');
                copy.type = 'button';
                copy.className = 'fp-ibtn';
                copy.innerHTML = CFG.icons.copy + '<span>' + CFG.labels.copy + '</span>';
                copy.addEventListener('click', function() {
                    var v = inp.value || '';
                    var span = copy.querySelector('span');
                    var done = function() {
                        copy.innerHTML = CFG.icons.check + '<span>' + CFG.labels.copied + '</span>';
                        setTimeout(function() {
                            copy.innerHTML = CFG.icons.copy + '<span>' + CFG.labels.copy + '</span>';
                        }, 1400);
                    };
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(v).then(done).catch(done);
                    } else {
                        inp.removeAttribute('readonly'); inp.select();
                        try { document.execCommand('copy'); done(); } catch (e) {}
                    }
                });

                wrap.appendChild(reveal);
                wrap.appendChild(copy);
            });

            // Pill toggles replacing each native checkbox. The native input
            // stays in the DOM (visually hidden) as the form's source of truth,
            // so Moodle's save path is unchanged.
            (CFG.toggles || []).forEach(function(id) {
                var cb = document.getElementById(id);
                if (!cb || cb.dataset.fpToggle === '1') { return; }
                cb.dataset.fpToggle = '1';
                cb.classList.add('fp-native-hidden');
                cb.setAttribute('tabindex', '-1');
                cb.setAttribute('aria-hidden', 'true');

                var wrap = document.createElement('span');
                wrap.className = 'fp-toggle-wrap';
                cb.parentNode.insertBefore(wrap, cb);
                wrap.appendChild(cb);

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'fp-toggle';
                btn.setAttribute('role', 'switch');
                var lbl = document.createElement('span');
                lbl.className = 'fp-toggle-label';

                // Dependent field to reveal only while the toggle is on.
                var revealId = (CFG.reveals || {})[id];
                var revealItem = revealId ? document.getElementById(revealId) : null;

                function sync() {
                    var on = cb.checked;
                    btn.classList.toggle('is-on', on);
                    btn.setAttribute('aria-checked', on ? 'true' : 'false');
                    lbl.textContent = on ? CFG.labels.toggleOn : CFG.labels.toggleOff;
                    if (revealItem) { revealItem.style.display = on ? '' : 'none'; }
                }
                // Drive the native checkbox with a real click so Moodle's
                // hide_if dependency manager fires and shows/hides the
                // dependent DRM Configuration ID field. (Setting .checked +
                // a synthetic 'change' does NOT trigger that logic.)
                btn.addEventListener('click', function() {
                    cb.click();
                    sync();
                });
                // Keep the toggle in sync if anything else flips the checkbox.
                cb.addEventListener('change', sync);

                wrap.appendChild(btn);
                wrap.appendChild(lbl);
                sync();

                // Rewrite the native "Default: No/Yes" hint to the
                // Enabled/Disabled wording shown beside the toggle.
                var item = btn.closest ? btn.closest('.form-item') : null;
                if (item) {
                    var di = item.querySelector('.form-defaultinfo');
                    if (di) {
                        di.textContent = di.textContent
                            .replace(new RegExp('\\\\b' + CFG.labels.defaultNo + '\\\\b'), CFG.labels.toggleOff)
                            .replace(new RegExp('\\\\b' + CFG.labels.defaultYes + '\\\\b'), CFG.labels.toggleOn);
                        // Drop it onto its own line below the toggle (Moodle's
                        // defaultsnext layout renders it inline next to the input).
                        di.style.display = 'block';
                    }
                }
            });

            // Custom accessible dropdown replacing each native <select>. The
            // native select stays in the DOM (visually hidden) and remains the
            // source of truth, so Moodle's form save is unchanged.
            function enhanceSelect(sel) {
                if (sel.dataset.fpSelect === '1') { return; }
                sel.dataset.fpSelect = '1';
                var wrap = document.createElement('div');
                wrap.className = 'fp-select-wrap';
                sel.parentNode.insertBefore(wrap, sel);
                wrap.appendChild(sel);
                sel.classList.add('fp-native-hidden');
                sel.setAttribute('tabindex', '-1');
                sel.setAttribute('aria-hidden', 'true');

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'fp-select';
                btn.setAttribute('aria-haspopup', 'listbox');
                btn.setAttribute('aria-expanded', 'false');
                var label = document.createElement('span');
                label.className = 'fp-select-label';
                var chev = document.createElement('span');
                chev.className = 'fp-select-chev';
                chev.innerHTML = CFG.icons.chevron;
                btn.appendChild(label);
                btn.appendChild(chev);

                var menu = document.createElement('ul');
                menu.className = 'fp-select-menu';
                menu.setAttribute('role', 'listbox');
                menu.hidden = true;

                var items = [];
                var active = -1;
                Array.prototype.forEach.call(sel.options, function(opt, i) {
                    var li = document.createElement('li');
                    li.className = 'fp-select-opt';
                    li.setAttribute('role', 'option');
                    var check = document.createElement('span');
                    check.className = 'fp-select-check';
                    check.innerHTML = CFG.icons.check;
                    var txt = document.createElement('span');
                    txt.textContent = opt.text;
                    li.appendChild(check);
                    li.appendChild(txt);
                    li.addEventListener('click', function() { choose(i); });
                    li.addEventListener('mousemove', function() { setActive(i); });
                    menu.appendChild(li);
                    items.push(li);
                });

                wrap.appendChild(btn);
                wrap.appendChild(menu);

                function sync() {
                    var idx = sel.selectedIndex;
                    label.textContent = idx >= 0 ? sel.options[idx].text : '';
                    items.forEach(function(li, i) {
                        var on = (i === idx);
                        li.classList.toggle('is-selected', on);
                        li.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                }
                function setActive(i) {
                    active = i;
                    items.forEach(function(li, j) { li.classList.toggle('is-active', j === i); });
                    if (items[i]) { items[i].scrollIntoView({ block: 'nearest' }); }
                }
                function open() {
                    menu.hidden = false;
                    wrap.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                    position();
                    setActive(sel.selectedIndex < 0 ? 0 : sel.selectedIndex);
                    document.addEventListener('mousedown', outside, true);
                    window.addEventListener('resize', position);
                    window.addEventListener('scroll', position, true);
                }
                // Open downward by default; flip above the button when there
                // isn't room below. Either way clamp max-height to the space
                // actually available (min 160px) so the menu's own scrollbar
                // engages instead of the list spilling off-screen.
                function position() {
                    var GAP = 6, MARGIN = 12, MINH = 160, CAP = 320;
                    var r = btn.getBoundingClientRect();
                    var below = window.innerHeight - r.bottom - GAP - MARGIN;
                    var above = r.top - GAP - MARGIN;
                    var up = below < MINH && above > below;
                    var space = Math.max(MINH, Math.min(CAP, up ? above : below));
                    menu.style.maxHeight = space + 'px';
                    if (up) {
                        menu.style.top = 'auto';
                        menu.style.bottom = 'calc(100% + ' + GAP + 'px)';
                    } else {
                        menu.style.bottom = 'auto';
                        menu.style.top = 'calc(100% + ' + GAP + 'px)';
                    }
                }
                function close() {
                    menu.hidden = true;
                    wrap.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                    document.removeEventListener('mousedown', outside, true);
                    window.removeEventListener('resize', position);
                    window.removeEventListener('scroll', position, true);
                }
                function outside(e) { if (!wrap.contains(e.target)) { close(); } }
                function choose(i) {
                    if (i < 0 || i >= sel.options.length) { return; }
                    sel.selectedIndex = i;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    sync();
                    close();
                    btn.focus();
                }

                btn.addEventListener('click', function() { if (menu.hidden) { open(); } else { close(); } });
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (menu.hidden) { open(); return; }
                        if (e.key === 'ArrowDown') { setActive(Math.min(items.length - 1, active + 1)); }
                        else if (e.key === 'ArrowUp') { setActive(Math.max(0, active - 1)); }
                        else { choose(active); }
                    } else if (e.key === 'Escape') {
                        if (!menu.hidden) { e.preventDefault(); close(); btn.focus(); }
                    } else if (e.key === 'Home') { e.preventDefault(); if (!menu.hidden) { setActive(0); } }
                    else if (e.key === 'End') { e.preventDefault(); if (!menu.hidden) { setActive(items.length - 1); } }
                });

                sync();
            }
            var selform = document.getElementById('adminsettings') || page;
            Array.prototype.forEach.call(selform.querySelectorAll('.fp-card-body select'), enhanceSelect);
        } catch (e) {
            if (window.console && console.warn) { console.warn('local_fastpix cards:', e); }
        }
    });
})();
</script>
SCRIPT;

$settings->add(new admin_setting_description(
    'local_fastpix/fp_cards',
    '',
    $fpcardstyle . $fpcardscript,
));
