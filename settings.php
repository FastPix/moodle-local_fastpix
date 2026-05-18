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
): string {
    $button = \html_writer::tag('button', get_string($labelkey, 'local_fastpix'), [
        'id'    => $buttonid,
        'type'  => 'button',
        'class' => 'btn btn-secondary',
    ]);
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
            status.textContent = 'Working…';
            status.style.color = '';
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
                      status.textContent = 'Failed: ' + (r.exception ? r.exception.message : r.error);
                      status.style.color = 'red';
                      return;
                  }
                  var data = r && r.data;
                  if (data && data.success) {
                      status.textContent = cfg.successTpl.replace('{\$a}', String(data[cfg.successField]));
                      status.style.color = 'green';
                  } else {
                      var msg = (data && (data.error || (data.errors && data.errors.join(', ')) || data.result)) || 'unknown';
                      status.textContent = 'Failed: ' + msg;
                      status.style.color = 'red';
                  }
              }).catch(function(err) {
                  btn.disabled = false;
                  status.textContent = 'Failed: ' + (err && err.message || err);
                  status.style.color = 'red';
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

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_credentials',
    new lang_string('settings_credentials', 'local_fastpix'),
    '',
));

$settings->add(new admin_setting_configtext(
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
$settings->add(new admin_setting_configtext(
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
        'Connected (latency {$a} ms)',
        'latency_ms',
    ),
));

// 2. Upload defaults.

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_upload_defaults',
    new lang_string('setting_section_upload_defaults', 'local_fastpix'),
    '',
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

$settings->add(new admin_setting_heading(
    'local_fastpix/heading_features',
    new lang_string('settings_features', 'local_fastpix'),
    '',
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

// Hide the DRM config id when the DRM feature flag is OFF (rule W12 double.
// Gate is enforced at runtime; this is just UI clarity).
$settings->hide_if(
    'local_fastpix/drm_configuration_id',
    'local_fastpix/feature_drm_enabled',
    'notchecked',
);

// 4. Webhooks.

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
$webhookurljson = json_encode([
    'urlId'     => $webhookurlid,
    'btnId'     => $webhookurlbtnid,
    'labelCopy' => get_string('button_copy_webhook_url', 'local_fastpix'),
    'labelDone' => get_string('button_copy_webhook_url_done', 'local_fastpix'),
], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
$webhookurlhtml = \html_writer::start_tag('div', [
    'style' => 'padding: 0.5rem 0 1rem 0; display: flex; align-items: center; '
             . 'gap: 0.75rem; flex-wrap: wrap; max-width: 720px;',
]);
$webhookurlhtml .= \html_writer::empty_tag('input', [
    'type'     => 'text',
    'id'       => $webhookurlid,
    'class'    => 'form-control',
    'value'    => $webhookurl,
    'readonly' => 'readonly',
    'onclick'  => 'this.select();',
    'style'    => 'flex: 1 1 320px; min-width: 0;',
]);
$webhookurlhtml .= \html_writer::tag('button', get_string('button_copy_webhook_url', 'local_fastpix'), [
    'id'    => $webhookurlbtnid,
    'type'  => 'button',
    'class' => 'btn btn-outline-secondary',
    'style' => 'flex: 0 0 auto;',
]);
$webhookurlhtml .= \html_writer::end_tag('div');
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
                var orig = cfg.labelCopy;
                btn.textContent = cfg.labelDone;
                setTimeout(function() { btn.textContent = orig; }, 1500);
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
    ),
));

// Push Moodle's "Save changes" action bar down a bit so it doesn't sit flush
// against the Send-test-event row above it.
$settings->add(new admin_setting_description(
    'local_fastpix/save_button_spacer',
    '',
    '<style>#adminsettings .form-actions, #adminsettings .form-buttons, '
    . '#page-admin-setting-local_fastpix .form-actions, '
    . '#page-admin-setting-local_fastpix .form-buttons '
    . '{ margin-top: 2rem !important; }</style>',
));
