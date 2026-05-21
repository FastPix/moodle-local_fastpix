# local_fastpix

A Moodle local plugin that connects your Moodle site to
[FastPix](https://www.fastpix.io) video hosting. It provides the shared
HTTP gateway, asset cache, webhook ingestion, and JWT signing used by
the FastPix activity module, filter, and editor plugins.

Use this plugin if you administer a Moodle site and want to add FastPix
video support. On its own, it ships only an admin settings page, a
webhook endpoint, and a health probe — there's no UI for students or
teachers until you also install the companion plugins.

| Property | Value |
|---|---|
| Release | 1.0.0 |
| Maturity | Stable |
| Requires | Moodle 4.5 LTS or later, PHP 8.1+ |
| License | [GNU GPL v3 or later](https://www.gnu.org/licenses/gpl-3.0.html) |
| Source | [moodle-local_fastpix on GitHub](https://github.com/FastPix/moodle-local_fastpix) |

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Install](#install)
- [Configure](#configure)
  - [API credentials](#api-credentials)
  - [Webhook](#webhook)
  - [DRM (optional)](#drm-optional)
  - [Health endpoint](#health-endpoint)
- [Privacy](#privacy)
- [Permissions](#permissions)
- [Support](#support)
- [License](#license)

## Features

- Direct upload and URL-pull workflows for video assets.
- Webhook ingestion with idempotency, per-asset ordering, and dual-secret
  rotation over a 30-minute window.
- Local RS256 JWT signing for private and DRM-protected playback.
- Optional DRM, controlled by a feature flag and a configuration ID. If
  you set only one, DRM stays disabled.
- Server-side request forgery (SSRF) protection on URL-pull sources.
- Full Moodle Privacy API support, including per-asset deletion under
  GDPR.
- Health check endpoint for monitoring.

## Requirements

Before you install, make sure your environment meets these
requirements:

- Moodle 4.5 LTS or later.
- PHP 8.1 or later (tested through PHP 8.3).
- A FastPix account with API credentials and a webhook signing secret.
- A shared Moodle Universal Cache (MUC) backend such as Redis,
  Memcached, or — on a single-server install with one PHP-FPM pool —
  the file store. The gateway circuit breaker and rate limiter rely on
  shared cache state, so they need a backend that all PHP workers can
  reach.

The plugin bundles one third-party library, `firebase/php-jwt` v6.10.0
(BSD-3-Clause), under `classes/vendor/php-jwt/`. There are no Composer
dependencies at runtime. For details, see `thirdpartylibs.xml`.

## Install

Choose one of the following methods.

### Install from the Moodle Plugins Directory

1. Sign in to your Moodle site as an administrator.
2. Go to **Site administration > Plugins > Install plugins**.
3. Search for **FastPix** and follow the prompts.

### Install from a ZIP file

1. Download the latest release from the
   [GitHub Releases page](https://github.com/FastPix/moodle-local_fastpix/releases).
2. Sign in to your Moodle site as an administrator.
3. Go to **Site administration > Plugins > Install plugins** and upload
   the ZIP file.

### Install from source

Run the following commands on your Moodle server:

```bash
cd /path/to/moodle
git clone https://github.com/FastPix/moodle-local_fastpix.git local/fastpix
php admin/cli/upgrade.php --non-interactive
```

When the install hook runs, the plugin generates a webhook signing
secret, a per-site user-hash salt, and the default feature flags (DRM
off). The RSA signing key for playback JWTs is created the first time
it's needed, after you save your API credentials.

## Configure

To open the settings page, go to **Site administration > Server >
FastPix**. You need the `local/fastpix:configurecredentials`
capability, which the Manager role has by default.

### API credentials

1. In your FastPix dashboard, go to **Settings > API Keys**.
2. Copy the API key and API secret.
3. In Moodle, paste both values into the FastPix settings page.

Moodle stores the secret in the `config_plugins` table using the
standard `admin_setting_configpasswordunmask` field. The settings page
masks the value in the browser, but the database doesn't encrypt it.
Protect your database backups accordingly.

### Webhook

To receive playback and processing events from FastPix:

1. On the Moodle settings page, copy the webhook URL. It looks like
   `https://your.moodle.example/local/fastpix/webhook.php`.
2. In the FastPix dashboard, add a new webhook destination and paste
   the URL.
3. Get the signing secret that Moodle generated at install time by
   running this command from your Moodle root:

   ```bash
   php -r 'define("CLI_SCRIPT", true); require "config.php";
     echo get_config("local_fastpix", "webhook_secret_current"), "\n";'
   ```

4. Paste the secret into the FastPix webhook configuration.
5. Subscribe to these events at minimum:
   - `video.media.created`
   - `video.media.ready`
   - `video.media.updated`
   - `video.media.failed`
   - `video.media.deleted`

When you rotate the secret later, the verifier accepts the previous
value for 30 minutes. This gives you time to update both sides without
losing webhook deliveries.

### DRM (optional)

DRM is disabled by default. To turn it on:

1. On the Moodle settings page, select **Enable DRM**.
2. Paste your FastPix **DRM Configuration ID** into the matching field.

You must set both. If either is missing, DRM stays disabled.

### Health endpoint

The plugin exposes a public health check at:

```
https://your.moodle.example/local/fastpix/health.php
```

The endpoint requires no authentication and is rate-limited to 30
requests per minute per IP address. It returns JSON with these fields:

- `status`
- `fastpix_reachable`
- `latency_ms`
- `timestamp`

Response codes:

- `200` — service is healthy.
- `503` — upstream failure.
- `429` — rate limit exceeded.

The endpoint works with monitoring tools such as Pingdom, UptimeRobot,
or Prometheus blackbox exporter.

## Privacy

This plugin includes a full Moodle Privacy API provider. The plugin
doesn't send raw user IDs to FastPix; instead, it sends a site-specific
HMAC value called `user_hash`. The webhook ledger is automatically
pruned after 90 days, and soft-deleted assets are permanently removed
after a 7-day grace window.

For full details, see **Site administration > Users > Privacy and
policies > Data registry** in your Moodle site.

## Permissions

| Capability                            | Default role |
|---------------------------------------|--------------|
| `local/fastpix:configurecredentials`  | Manager      |

The related activity-side capability `mod/fastpix:uploadmedia` is
defined by the `mod_fastpix` plugin, not this one.

## Support

- File an issue on the
  [issue tracker](https://github.com/FastPix/moodle-local_fastpix/issues).
- Read the [changelog](CHANGELOG.md) for release notes.
- For contribution guidelines, see [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

Copyright © 2026 FastPix Inc. Released under the GNU GPL v3.0 or
later. For the full license text, see [`LICENSE`](LICENSE).
 