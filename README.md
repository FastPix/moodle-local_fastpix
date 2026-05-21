# FastPix for Moodle

Add video uploads and playback to Moodle using
[FastPix](https://www.fastpix.io). This is the **core plugin** that
the FastPix activity, filter, and editor plugins all depend on. It
handles uploads, webhooks, playback tokens, and admin settings.

| | |
|---|---|
| Version | 1.0.0 (stable) |
| Needs | Moodle 4.5+ · PHP 8.1+ |
| Licence | GPL v3 or later |

---

## What you need before you install

| Thing | Why |
|---|---|
| **Moodle 4.5 LTS or newer** | Older versions don't have the APIs we use |
| **PHP 8.1, 8.2, or 8.3** | Moodle 4.5+ requires 8.1 minimum |
| **MySQL 8.0+ / MariaDB 10.6+ / PostgreSQL 13+** | Any database Moodle itself supports |
| **A FastPix account** | API key + secret + a webhook destination |
| **Moodle cron running every minute** | This is how webhook events get processed (more below) |
| **A public URL** (only for production) | FastPix needs to reach your Moodle to send webhooks |

PHP extensions: `openssl`, `curl`, `json`, `hash`, `mbstring`. These
come with any normal Moodle install.

No Composer dependencies. We ship `firebase/php-jwt` v6.10.0 already
bundled in `classes/vendor/`.

> **Note on MySQL versions:** This plugin works with MySQL 8.0+. If
> Moodle complains *"MySQL 8.4 is required and you are running 8.0.x"*,
> that's a **Moodle 5.1+ core requirement**, not ours. On Moodle 4.5
> LTS, MySQL 8.0 (the version MAMP ships) works perfectly. For test
> setups, we recommend **Moodle 4.5 LTS** — it's the most representative
> production environment and avoids this version conflict.

---

## Install (3 steps)

### Step 1: Drop the plugin into Moodle

Either upload the ZIP via **Site admin → Plugins → Install plugins**,
or from a terminal:

```bash
cd /path/to/moodle
git clone https://github.com/tharunbudidha27/local-plugin.git local/fastpix
php admin/cli/upgrade.php --non-interactive
```

### Step 2: Add your FastPix credentials

Go to **Site admin → Server → FastPix** and fill in:

1. **API key** and **API secret** — from your FastPix dashboard
   (Settings → API Keys).
2. **Webhook URL** — *don't type anything*. Moodle shows you the URL
   it expects; click the **Copy** button next to it.
3. Open the **FastPix dashboard → Webhooks**, paste that URL as a new
   destination, and **subscribe to these events**:
   - `video.media.created`
   - `video.media.ready`
   - `video.media.updated`
   - `video.media.failed`
   - `video.media.deleted`
   - `video.upload.media_created`
4. **Copy the signing secret** FastPix gives you for that destination,
   come back to Moodle, paste it into the **Webhook signing secret**
   field, and save.

That's it for the manual setup. The plugin handles everything else
automatically (signing keys, key registration, etc.).

### Step 3: Make sure Moodle cron is running

This is non-optional. Webhooks arrive, get stored, and then a
background job updates the database — but the background job only
runs when Moodle cron runs. Without cron, videos stay in
"Processing" forever.

**Linux server:** add this to `/etc/cron.d/moodle`:
```
* * * * * www-data /usr/bin/php /var/www/moodle/admin/cli/cron.php > /dev/null 2>&1
```

**Docker:** make sure your `docker-compose.yml` has a cron sidecar
running `while true; do php admin/cli/cron.php; sleep 60; done`.

**MAMP (Mac local dev):** open a Terminal and leave this running:
```bash
while true; do
  /Applications/MAMP/bin/php/php8.2.x/bin/php \
    /Applications/MAMP/htdocs/moodle/admin/cli/cron.php
  sleep 30
done
```

**Managed Moodle hosts** (Bitnami, MoodleCloud, Catalyst): cron is
already running for you. Verify at **Site admin → Server → Tasks →
Cron** — "Last run" should be less than 1 minute ago.

---

## Test the install

Upload a short test video through any FastPix-enabled activity.
Within about a minute, it should show as "Ready" and play in the
browser. If it doesn't, see [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

You can also check the health endpoint:
```
curl https://your-moodle.com/local/fastpix/health.php
```
Expect HTTP 200 and a JSON body with `"status": "ok"`.

---

## What the plugin does

- **Video uploads** — direct browser upload or URL pull, with a
  60-second dedup window.
- **Webhook ingestion** — verified, idempotent, ordered per asset.
- **JWT signing** — RS256 tokens for private/DRM playback. The key
  is auto-generated on first use and registered with FastPix
  automatically. No manual key paste.
- **DRM support** — optional, off by default. Enable in admin.
- **Privacy** — full GDPR support via Moodle's Privacy API. Raw user
  IDs are never sent to FastPix (they're HMAC-hashed first).
- **Health endpoint** — `/local/fastpix/health.php` for uptime
  monitoring.

---

## Permissions

| Capability | Default role |
|---|---|
| `local/fastpix:configurecredentials` | Manager only |

That's the only capability this plugin defines. The upload-media
capability lives in the `mod_fastpix` activity plugin.

---

## Common gotchas (quick reference)

| Problem | Fix |
|---|---|
| Video stuck on "Processing" / "Preparing" | Cron isn't running. See Step 3 above. |
| Webhook returns 401 in FastPix dashboard | The signing secret in Moodle doesn't match. Re-paste it. |
| Webhook returns 400 in FastPix dashboard | Upgrade to plugin version 2026051201+ (validation-ping fix). |
| Private videos don't play, public ones do | Signing key not bootstrapped. See TROUBLESHOOTING §4. |
| Can't reach Moodle from FastPix (local dev) | Use `ngrok http 8000` and paste the public URL into FastPix. |

For anything more specific, see [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

---

## Support

- **Issues:** <https://github.com/tharunbudidha27/local-plugin/issues>
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

## Licence

Copyright © 2026 FastPix Inc. GPL v3.0 or later. See `LICENSE`.
