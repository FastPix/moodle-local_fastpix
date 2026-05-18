# local_fastpix

Moodle local plugin that integrates [FastPix](https://www.fastpix.io)
video hosting with Moodle. It owns the HTTP gateway, asset cache,
webhook ingestion, and JWT signing that the FastPix activity, filter,
and editor plugins consume. On its own it ships an admin settings
page, a webhook endpoint, and a health probe — no learner-facing UI.

| | |
|---|---|
| **Release** | 1.0.0 |
| **Maturity** | Stable |
| **Requires** | Moodle 4.5 LTS or later · PHP 8.1+ |
| **Licence** | [GNU GPL v3 or later](https://www.gnu.org/licenses/gpl-3.0.html) |
| **Source** | <https://github.com/FastPix/moodle-local_fastpix> |

## Features

- Direct upload and URL-pull workflows.
- Webhook ingestion with idempotency, per-asset ordering, and
  30-minute dual-secret rotation.
- Local RS256 JWT signing for private and DRM-protected playback.
- Optional DRM, gated on both a feature flag and a configuration ID.
- SSRF guard on URL-pull sources.
- Full Moodle Privacy API provider with GDPR per-asset deletion.
- Health endpoint for monitoring.

## Requirements

- Moodle 4.5 LTS or later.
- PHP 8.1+ (tested through 8.3).
- A FastPix account with API credentials and a webhook signing secret.
- A shared MUC backend (Redis, Memcached, or file store on
  single-FPM installs). The gateway circuit breaker and rate limiter
  rely on shared cache state.

The plugin vendors one third-party library, `firebase/php-jwt`
v6.10.0 (BSD-3-Clause), under `classes/vendor/php-jwt/`. No Composer
dependencies at runtime — see `thirdpartylibs.xml`.

## Installation

**Via the Moodle Plugins Directory.** Site administration → Plugins →
Install plugins → search for **FastPix** and follow the prompts.

**From a ZIP.** Download the latest release from
[GitHub Releases](https://github.com/FastPix/moodle-local_fastpix/releases),
then upload via Site administration → Plugins → Install plugins.

**From source.**

```
cd /path/to/moodle
git clone https://github.com/FastPix/moodle-local_fastpix.git local/fastpix
php admin/cli/upgrade.php --non-interactive
```

The install hook seeds the webhook signing secret, the per-site user
hash salt, and the default feature flags (DRM off). The RSA signing
key for playback JWTs is created lazily on first use, after you save
API credentials.

## Configuration

Navigate to **Site administration → Server → FastPix**. The page
requires the `local/fastpix:configurecredentials` capability, granted
to the Manager archetype by default.

### API credentials

Paste the API key and API secret from your FastPix dashboard
(**Settings → API Keys**). The secret is stored in Moodle's
`config_plugins` table using the standard `admin_setting_configpasswordunmask`
pattern — the UI masks the value but the database does not encrypt
it. Protect database backups accordingly.

### Webhook

Copy the webhook URL shown on the settings page (it looks like
`https://your.moodle.example/local/fastpix/webhook.php`) into a new
webhook destination in the FastPix dashboard, then paste the
matching signing secret that Moodle generated at install:

```
php -r 'define("CLI_SCRIPT", true); require "config.php";
  echo get_config("local_fastpix", "webhook_secret_current"), "\n";'
```

Subscribe at minimum to `video.media.created`, `video.media.ready`,
`video.media.updated`, `video.media.failed`, and
`video.media.deleted`. After rotating the secret, the verifier
accepts the previous value for 30 minutes so both sides can be
updated without a delivery gap.

### DRM (optional)

DRM is disabled by default. To enable it, tick **Enable DRM** on the
settings page and paste your FastPix **DRM Configuration ID**. Both
must be set; either alone fails closed.

### Health endpoint

`https://your.moodle.example/local/fastpix/health.php` — public, no
auth, per-IP rate-limited at 30 req/min. Returns JSON with `status`,
`fastpix_reachable`, `latency_ms`, and `timestamp`. HTTP 200 on
success, 503 on upstream failure, 429 when rate-limited. Suitable
for Pingdom, UptimeRobot, Prometheus blackbox, etc.

## Privacy

`local_fastpix` ships a full Moodle Privacy API provider. The plugin
never transmits raw user IDs to FastPix — a site-specific HMAC
(`user_hash`) is sent instead. The webhook ledger is pruned after
90 days, and soft-deleted assets are hard-purged after a 7-day
grace window. Details are listed under **Site administration →
Users → Privacy and policies → Data registry**.

## Permissions

| Capability                            | Default archetype |
|---------------------------------------|-------------------|
| `local/fastpix:configurecredentials`  | Manager           |

The activity-side capability `mod/fastpix:uploadmedia` is defined
and owned by `mod_fastpix`, not by this plugin.

## Support

- **Issues:** <https://github.com/FastPix/moodle-local_fastpix/issues>
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)

## Licence

Copyright © 2026 FastPix Inc. Released under the GNU GPL v3.0 or
later. See `LICENSE` for the full text.
