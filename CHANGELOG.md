# Changelog

All notable changes to `local_fastpix` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this
project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-05-21

Initial release. FastPix video integration foundation plugin for
Moodle 4.5 LTS+.

### Added
- HTTP gateway as the single boundary to the FastPix API, with retry,
  circuit breaker, idempotency keys, and structured logging.
- Direct upload and URL-pull workflows, with a 60-second dedup window
  and an SSRF guard on URL-pull sources.
- Webhook ingestion: signature verification with `hash_equals`,
  idempotent ledger, per-asset locking with total ordering, and a
  30-minute dual-secret rotation window.
- Local RS256 JWT signing for private and DRM-protected playback
  (`media:` and `drm:` audiences). Signing key bootstraps automatically
  and re-mints when API credentials change.
- Asset metadata cache with dual-key MUC invalidation and read-path
  lazy fetch.
- Optional DRM, gated on both a feature flag and a configuration ID.
- Scheduled cleanup tasks: orphan sweeper, webhook ledger pruning
  (90-day retention), soft-delete purge (7-day grace), and GDPR
  delete retry.
- Full Moodle Privacy API provider with GDPR per-asset deletion; raw
  user IDs are never sent to FastPix (a site-specific HMAC is used).
- Admin settings page (credentials, DRM, webhook secret) and a
  rate-limited health endpoint for monitoring.

### Security
- Seven non-negotiables enforced: RS256-only signing, no remote token
  minting, gateway-only HTTP, `hash_equals` signature checks, no
  cross-plugin imports, no `_or_fetch` on write paths, no Composer
  dependencies.
