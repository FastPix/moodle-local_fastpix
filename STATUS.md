# Status: v1.0.0 STABLE — initial release

Initial release. The plugin clears every gate for production:

- **221/221 PHPUnit tests passing, 200,560 assertions.**
- **Coverage gate ALL GREEN** — 25 surfaces meet architecture targets
  (gateway 95%, jwt_signing 95%, verifier 90%, projector 90%, others
  85%); 16 surfaces exempted via ADR-014 with documented alternative
  testing strategies.
- **Seven non-negotiables grep-clean** — no HS256, no `createToken`,
  no `curl_*`, no `_or_fetch` on write paths, no cross-plugin code
  refs, `hash_equals` on signatures, no `composer.json`.
- **End-to-end verified against the FastPix sandbox 2026-05-12** —
  URL pull → webhook ingestion → asset projection → RS256 JWT mint
  with `aud=media:<playback_id>` (see CHANGELOG `[1.0.0]`).

Maturity: `MATURITY_STABLE`, release `1.0.0`, version `2026052100`.

## What works

### Foundation
- 3-layer architecture (endpoint → service → gateway) enforced.
- All 4 public services (`asset_service`, `upload_service`,
  `jwt_signing_service`, `playback_service`) match the documented
  surface in ADR-013.
- Gateway is the only HTTP boundary (rule A2). Pinned to IPv4 via
  `force_ip_resolve` to avoid Docker-bridge IPv6 dead-ends.

### Webhooks
- Verifier accepts FastPix's canonical signature shape
  `base64(hmac(base64_decode(SECRET), body))` — empirically verified
  against the FastPix sandbox 2026-05-07.
- Verify-record-enqueue pipeline extracted to
  `\local_fastpix\webhook\processor` so both HTTP and admin
  "Send test event" drive the same flow.
- Validation-ping path returns 200 on empty/`{}` bodies so FastPix
  dashboard's URL validator accepts the endpoint.
- Idempotent (UNIQUE on `provider_event_id`, dup caught and 200'd).
- Per-asset lock around projection; cache invalidation inside the
  lock; both keys (`fastpix_id` + `playback_id`) invalidated.
- Total ordering with lex tiebreak on equal timestamps.
- 30-min dual-secret rotation window with audit event
  (`\local_fastpix\event\webhook_secret_rotated`).
- 90-day ledger retention (rule W9) via `webhook_event_pruner`.
- 60 req/min/IP rate limit with fail-open on cache failure.

### JWT signing
- RS256 only (rule S1). HS256 never appears in production code.
- `kid` in header + payload. TTL 3600s. `sub` reserved (empty) to
  avoid raw-userid leak (rule S9).
- No JWT caching anywhere — MUC, static, or DB (rule S10).
- Per-call mint (1–5 ms).

### Asset cache
- Dual-key MUC (`fastpix_id` + `playback_id`) via
  `\local_fastpix\util\cache_keys` (single source of truth).
- Read-path lazy fetch via `get_by_fastpix_id_or_fetch` (only on
  cold-start for assets viewed before they're in the ledger).
- Forbidden on write paths (rule W7) — projector, scheduled tasks,
  and privacy provider only use `get_by_fastpix_id`.
- `get_by_upload_session_id` for the mod_fastpix Phase B handoff
  (ADR-013).

### Soft-delete + GDPR
- `asset_service::soft_delete()` stamps `deleted_at`.
- `purge_soft_deleted_assets` daily task hard-deletes rows past the
  7-day grace window with FK-cascade to `local_fastpix_track` (rule
  W10). Boundary tested at 6d23h (kept) / 7d1m (purged).
- Separate `asset_cleanup` handles GDPR-pending retry path with a
  10-attempt cap.

### Upload + URL pull
- Direct upload + URL pull, both via `upload_service`. 60-second
  dedup on both branches (rule W11).
- SSRF guard covers all 14 attack classes: non-https,
  credentials-in-URL, loopback (127/8 + `[::1]`), RFC1918, link-local
  (169.254/16 incl. AWS metadata), `localhost`, `*.local`,
  IPv6 ULA / link-local / NAT64 / IPv4-mapped, unresolvable hostnames.

### DRM
- Double gate: `feature_flag_service::drm_enabled()` requires BOTH
  the `feature_drm_enabled` checkbox AND a non-empty
  `drm_configuration_id` (rule W12).
- Admin UI hides the configuration_id field when the checkbox is off
  (`hide_if` widget dependency).

### Operational
- Privacy provider declares all three retention windows in
  `get_metadata`: 90-day webhook ledger, 7-day soft-delete purge,
  90-day GDPR-pending hard delete.
- `health.php` JSON endpoint wraps `gateway::health_probe()`.
- Backfill CLI (`cli/backfill_playback_ids.php`) for repairing
  pre-fix assets through the proper projector path.
- `TROUBLESHOOTING.md` documents recovery for the common
  dev-environment cron-not-running symptom.

## Architectural decisions

- **ADR-012** (`docs/adr/ADR-012-capability-ownership.md`):
  `mod/fastpix:uploadmedia` ownership stays in `mod_fastpix`.
- **ADR-013** (`docs/adr/ADR-013-playback-service-and-asset-lookup-by-session.md`):
  `\local_fastpix\service\playback_service::resolve` is the
  chokepoint for JWT minting. mod_fastpix MUST go through this
  service — direct use of `jwt_signing_service` is a PR-3 violation.
- **ADR-014** (`docs/adr/ADR-014-coverage-exemptions.md`):
  16 surface classes are exempt from the unit-coverage gate (admin
  glue, typed exceptions, external-endpoint wrappers, scheduled-task
  shells, privacy provider, rate_limiter fail-open catch) with
  documented alternative testing strategies (Behat, integration,
  manual smoke).

## Operational verification (completed 2026-05-12)

Verified against a live FastPix tenant before the STABLE bump:

1. End-to-end upload via the FastPix dashboard against a production
   webhook URL — asset row appears with `playback_id`. ✓
2. Webhook secret rotation flow — `webhook_secret_rotated_at`
   updates, audit row appears in `mdl_logstore_standard_log`. ✓
3. JWT playback against the FastPix CDN — signed token fetches
   `.m3u8` manifest, HTTP 200 + `#EXTM3U` body. ✓
4. `tools/coverage.sh` re-runs green from a clean checkout. ✓

## Known limitations (carried into 1.0.0)

- **Admin Test Connection / Send Test Event buttons** are visible but
  inert. They depend on AMD modules built by Moodle's grunt
  toolchain, which is not present in this dev container. CLI
  equivalents are documented inline in `settings.php` (`gateway::
  health_probe()`, `cli/webhook_loopback_test.php`).
- **Credentials are stored as plaintext in `mdl_config_plugins`** —
  rule S8, disclosed in `README.md`. Protect database backups and
  the `local/fastpix:configurecredentials` capability accordingly.
- **`mod_fastpix` / `filter_fastpix` / `tinymce_fastpix` not present
  yet.** This plugin is the foundation; the three surface plugins are
  separate Moodle plugin repos and have their own development
  systems. The consumer-contract docs in `mod_fastpix/.claude/` list
  exactly what they consume from us.

## Don't push commits that

- introduce `HS256`, `createToken`, `curl_*` outside `classes/api/`,
  `_or_fetch` on write paths, cross-plugin namespace imports, `===`
  on signatures, or a `composer.json`.
- lower the per-class coverage gate (85/90/95). If a class is
  genuinely impossible to unit-test, it goes in ADR-014's exemption
  list, not on a gate reduction.
- bump `MATURITY_STABLE` without first running the operational
  verification queue above.
