# Changelog

All notable changes to `local_fastpix` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this
project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-05-12

End-to-end production-verified against the FastPix sandbox. Maturity
bumped from BETA → STABLE.

### Added
- Real-FastPix verification record on 2026-05-12: URL pull → webhook
  ingestion → asset projection → JWT minting with RS256 +
  `aud=media:<playback_id>` — all subsystems green against a live
  FastPix tenant.

### Fixed
- `credential_service::ensure_signing_key()` — read
  `$response->data->id` (FastPix's `{success, data}` envelope) instead
  of `$response->id`, and detect whether `privateKey` arrived
  base64-encoded vs raw to avoid double-encoding. Both bugs were
  masked by unit-test mocks; only exercised end-to-end on a real
  FastPix call. Empty-value guard added so a failed parse no longer
  silently overwrites config rows.

## [1.0.0-dev] — 2026-05-11

Production-readiness cleanup. The codebase clears the 2026-05-11
adversarial review at architecture targets (gateway 95%, jwt_signing
95%, verifier/projector 90%, others 85%) with 16 surface classes
exempted via ADR-014 for alternative testing strategies. Tag pending
bump to `MATURITY_STABLE` once operational verification is complete on
production tenants.

### Added
- `\local_fastpix\service\playback_service::resolve()` — chokepoint for
  JWT minting consumed by sibling plugins (ADR-013).
- `\local_fastpix\service\asset_service::get_by_upload_session_id()` —
  session → asset lookup for mod_fastpix Phase C handoff.
- `\local_fastpix\task\purge_soft_deleted_assets` — daily 7-day
  hard-purge of soft-deleted assets with FK-cascade to
  `local_fastpix_track` and dual cache-key invalidation (rule W10).
- `\local_fastpix\exception\asset_not_ready` — typed exception for
  playback_service when status != ready.
- `\local_fastpix\util\cache_keys` — single source of truth for the
  asset MUC dual-key formula.
- `\local_fastpix\webhook\processor` — extracted verify-record-enqueue
  pipeline so both HTTP webhook and admin "Send test event" drive the
  same code path.
- `cli/backfill_playback_ids.php` — operator CLI to repair pre-fix
  assets whose `playback_id` is null. Re-queues original webhook
  events through the proper projector path.
- ADR-013 (playback_service surface), ADR-014 (coverage exemptions).
- Coverage gate exemption mechanism — `tools/coverage_exemptions.json`
  loaded by `tools/coverage_gate.php`; refuses to run when the JSON is
  missing or malformed.
- Admin web services: `local_fastpix_test_connection`,
  `local_fastpix_send_test_event` (gated on
  `local/fastpix:configurecredentials`).
- Admin button widgets + AMD modules (`amd/build/test_connection.min.js`,
  `amd/build/send_test_event.min.js`).
- Webhook secret rotation widget
  (`\local_fastpix\admin\setting_webhook_secret`) — 30-minute
  dual-secret rollover window with audit event
  (`\local_fastpix\event\webhook_secret_rotated`).

### Changed
- Webhook ledger retention raised from 14 days to **90 days** to match
  rule W9 (`webhook_event_pruner`).
- Verifier accepts FastPix's canonical signature shape
  `base64(hmac_sha256(base64_decode(SECRET), body))` — production
  format empirically verified against the FastPix sandbox 2026-05-07.
  Legacy raw-string / hex output formats remain gated behind
  `LOCAL_FASTPIX_DEBUG_VERIFIER` for synthetic test fixtures.
- Projector `apply_first_playback_id()` now accepts `public`,
  `private`, and `drm` access policies (was private/drm only). Real
  FastPix `video.media.created` and `video.media.ready` payloads carry
  `public` by default; the prior filter dropped every real upload.
- Gateway pins to IPv4 (`force_ip_resolve => 'v4'`) — Docker bridge
  networks without IPv6 routes were intermittently hitting
  `ConnectException` through Happy-Eyeballs.
- Gateway log line enriched with `method`, `host`, `path`, `request_id`
  for ops correlation; `X-Request-Id` propagated to FastPix.
- Gateway retry policy adds **408 (Request Timeout)** to the
  retryable-status set.
- Gateway response body capped at **5 MiB**; oversize responses throw
  `gateway_invalid_response('response_too_large')` before decode.
- `user_hash_salt` entropy bumped from 32 to **64 chars** (rule S9).
  Existing installs see a one-time salt rotation on upgrade.
- Admin endpoints (`test_connection`, `send_test_event`) now gate on
  `local/fastpix:configurecredentials` instead of `moodle/site:config`
  — preserves least-privilege delegation per the v1.0 review M1
  finding.
- Privacy provider declares all three retention windows in
  `get_metadata()`: 90-day webhook ledger, 7-day soft-delete purge,
  90-day GDPR-pending hard delete.

### Removed
- `local_fastpix_sync_state` table — was reserved for an ADR-003 that
  never landed. Dropped from `db/install.xml` and via
  `db/upgrade.php` savepoint.

### Fixed
- `apply_first_playback_id` regression that caused all newly-uploaded
  assets to reach `status=ready` with `playback_id=null` (every
  account with default `public` access policy was affected).
- URL-pull dedup window — same `(userid, sha256(source_url))` within
  60 seconds now returns the existing session_id with `deduped=true`
  (rule W11 extended).
- `after_config_callback` documented with a hard "no synchronous HTTP"
  invariant; regression test
  (`tests/after_config_callback_test.php`) pins the contract with a
  trip-wire gateway mock.

### Security
- All seven non-negotiables hold (no HS256, no `createToken`, no
  `curl_*`, no `_or_fetch` on write paths, no cross-plugin imports,
  `hash_equals` on signatures, no `composer.json`).
