# Troubleshooting

Something not working? Match your symptom in the table below, then
jump to that section.

| What you're seeing | Section |
|---|---|
| Video stuck on "Processing" or "Preparing" forever | [§1 Cron not running](#1-cron-not-running) |
| FastPix dashboard shows 400 when verifying the webhook URL | [§2 Webhook 400](#2-webhook-returns-400) |
| FastPix dashboard shows 401 for delivered events | [§3 Webhook 401](#3-webhook-returns-401) |
| Public videos play, private ones don't | [§4 Signing key](#4-private-videos-dont-play) |
| Some videos play, specific ones are stuck | [§5 Per-asset check](#5-one-specific-video-is-stuck) |
| Local data is correct but UI still says "Preparing" | [§6 mod_fastpix issue](#6-data-looks-fine-but-ui-still-says-preparing) |
| Moodle itself is slow or unresponsive | [§7 Server overloaded](#7-moodle-is-slow-or-unresponsive) |

All commands assume Docker. If you're on Linux/MAMP/managed Moodle,
drop the `docker exec moodle-docker-webserver-1` prefix and run
directly.

---

## §1 Cron not running

**This causes ~80% of all issues.** The plugin receives webhooks but
needs Moodle cron to process them. Without cron, status stays
"Processing" forever even though FastPix is done.

### Check

```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
foreach ($DB->get_records_sql(
  "SELECT event_type, status, COUNT(*) c
   FROM {local_fastpix_webhook_event}
   GROUP BY event_type, status") as $r) {
  echo $r->event_type." | ".$r->status." | ".$r->c.PHP_EOL;
}'
```

If any line ends with `pending` → cron isn't draining → continue below.

If everything is `processed` → cron is fine, your issue is elsewhere.

### Fix

**Run cron manually right now** to drain the existing backlog:
```bash
docker exec moodle-docker-webserver-1 php /var/www/html/admin/cli/cron.php
```

Then make sure cron keeps running automatically:

| Where you run Moodle | What to do |
|---|---|
| Linux server | Add `* * * * * www-data /usr/bin/php /var/www/moodle/admin/cli/cron.php > /dev/null 2>&1` to `/etc/cron.d/moodle` |
| Docker | `docker ps \| grep cron` — start it if it's stopped, add a cron sidecar if there isn't one |
| MAMP | Open Terminal: `while true; do <mamp-php> <moodle>/admin/cli/cron.php; sleep 30; done` |
| Managed host (Bitnami, MoodleCloud) | Already running. Verify at Site admin → Server → Tasks → Cron |

After cron runs once, your existing stuck videos should flip to
"Ready" within a minute. New uploads work normally.

---

## §2 Webhook returns 400

**Cause:** FastPix's URL-verification probe sends a body shape the
plugin didn't recognize. Fixed in plugin version `2026051201`.

### Check your plugin version

```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
echo get_config("local_fastpix", "version").PHP_EOL;'
```

If it's older than `2026051201`, upgrade the plugin.

### Still 400 after upgrade?

Look at the access log to see what FastPix is sending:
```bash
docker exec moodle-docker-webserver-1 tail -20 /var/log/apache2/error.log \
  | grep validation_ping
```

You should see `"event":"webhook.validation_ping"` lines with the
probe body. If FastPix is sending something with `id` and `type`
fields that's still failing, open an issue with the captured body.

---

## §3 Webhook returns 401

**Cause:** The webhook signing secret in Moodle doesn't match what
FastPix is signing with.

### Fix

1. Open the FastPix dashboard → Webhooks → your destination.
2. Copy the signing secret displayed there.
3. Open Moodle admin → Server → FastPix → Webhook signing secret.
4. Paste, save.

### Common gotchas

- **Trailing whitespace** when copying. Re-copy carefully.
- **Wrong destination** — if you have multiple webhook destinations
  in FastPix, make sure you grabbed the secret for the one pointing
  at THIS Moodle.
- **Secret rotated** on FastPix side but not in Moodle.

After you save a new secret in Moodle, the previous secret keeps
working for 30 minutes (so you can update both sides without losing
events).

---

## §4 Private videos don't play

You'll know this is the issue if:
- Public videos play fine.
- Private videos either spin forever or show 403.

**Cause:** The JWT signing key hasn't been generated yet.

### Fix

The key is generated on first need. Force it now:

```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
\local_fastpix\service\credential_service::instance()->ensure_signing_key();
echo "key id = ".get_config("local_fastpix", "signing_key_id").PHP_EOL;'
```

This generates the keypair locally and automatically registers the
public key with FastPix. **You do not need to paste a public key
into the FastPix dashboard manually** — the plugin does it for you.

If the command fails, check that API key and API secret are saved
in Moodle admin first (the bootstrap needs them).

---

## §5 One specific video is stuck

Other videos work, but a specific one is stuck on "Processing".

### Find the upload session ID

In the URL when viewing the activity, or:
```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
foreach ($DB->get_records_sql(
  "SELECT id, upload_id, state FROM {local_fastpix_upload_session}
   ORDER BY id DESC LIMIT 10") as $r) {
  echo "id=".$r->id." state=".$r->state.PHP_EOL;
}'
```

### Inspect that session

Replace `19` with your session ID:
```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
$r = $DB->get_record_sql(
  "SELECT u.state, u.fastpix_id u_fpid, a.status, a.playback_id
   FROM {local_fastpix_upload_session} u
   LEFT JOIN {local_fastpix_asset} a ON a.fastpix_id = u.fastpix_id
   WHERE u.id = ?", [19]);
echo json_encode($r).PHP_EOL;'
```

### What it means

| Result | Diagnosis | What to do |
|---|---|---|
| `state=orphaned` | Upload never finished at FastPix. The video bytes never made it to FastPix's storage. | Delete the activity and re-upload. |
| `state=pending, u_fpid=NULL` | Upload created in Moodle, but no webhook has arrived yet from FastPix. | Wait a minute. If still pending: check §1 (cron) and §3 (signature). |
| `state=created, status=null` (no asset) | Webhook linked the session but no asset row exists. | Run `cli/backfill_playback_ids.php --apply` then cron. |
| `state=created, status=ready, playback=set` | Everything is correct. The bug is somewhere else (cache, UI, FastPix CDN). | See §6. |

---

## §6 Data looks fine but UI still says "Preparing"

If `local_fastpix_asset` shows `status=ready` with a `playback_id`,
but the activity page in Moodle still shows "Preparing your video",
the bug is **not in this plugin**.

### Verify with one command

Replace `<fastpix-id>` and `<userid>`:
```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
$r = \local_fastpix\service\playback_service::resolve("<fastpix-id>", <userid>);
echo json_encode($r, JSON_PRETTY_PRINT).PHP_EOL;'
```

If you get a JSON object with `playbackid`, `playbacktoken`, and
`expiresatts` — this plugin is doing its job correctly.

### Confirm FastPix CDN accepts the token

```bash
curl -sI "https://stream.fastpix.io/<playbackid>.m3u8?token=<playbacktoken>"
```

- **HTTP 200** → full chain works. The bug is in mod_fastpix UI.
  Report it to the mod_fastpix maintainer.
- **HTTP 403** → FastPix rejected the JWT. Check the FastPix
  dashboard → Signing keys to confirm your key is registered and
  enabled.
- **HTTP 404** → playback_id no longer exists on FastPix's side.
  Asset was deleted there. Re-upload.

---

## §7 Moodle is slow or unresponsive

After heavy debugging (lots of admin reloads, video uploads, cache
purges all at once), Moodle can wedge.

### Quick fix

```bash
docker restart moodle-docker-webserver-1
```

This kills any stuck Apache workers and restarts cleanly. ~10 seconds
of downtime.

### Why it happens

Each Apache worker holds memory (50–150 MB). If too many get stuck
waiting on DB locks or slow requests, the pool runs out. New
requests sit in queue and time out. Restart clears it.

This is a local-dev issue. Production traffic patterns don't cause
this.

---

## Useful one-liners

### See the last 5 webhook events
```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
foreach ($DB->get_records("local_fastpix_webhook_event", null,
  "received_at DESC", "received_at, status, event_type, last_error", 0, 5) as $r) {
  echo date("H:i:s", $r->received_at)." | ".$r->status." | ".
       $r->event_type." | ".($r->last_error ?? "").PHP_EOL;
}'
```

### See the last 5 assets
```bash
docker exec moodle-docker-webserver-1 php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";
foreach ($DB->get_records("local_fastpix_asset", null,
  "timemodified DESC", "fastpix_id, status, playback_id, access_policy", 0, 5) as $r) {
  echo $r->fastpix_id." | ".$r->status." | ".
       ($r->playback_id ?? "NULL")." | ".$r->access_policy.PHP_EOL;
}'
```

### Force-drain cron once
```bash
docker exec moodle-docker-webserver-1 php /var/www/html/admin/cli/cron.php
```

### Repair old assets that have no playback_id
```bash
docker exec moodle-docker-webserver-1 \
  php /var/www/html/local/fastpix/cli/backfill_playback_ids.php --apply
docker exec moodle-docker-webserver-1 \
  php /var/www/html/admin/cli/cron.php
```

---

## Still stuck?

- **Open an issue:** <https://github.com/tharunbudidha27/local-plugin/issues>
- **Include:** the symptom, the relevant `docker exec` output from
  above, and your Moodle + PHP versions.

Don't patch the database directly to "fix" stuck assets — it bypasses
the locking and cache invalidation logic and can corrupt state.
Always go through the projector via `backfill_playback_ids.php`.
