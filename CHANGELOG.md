# Changelog

All notable changes to `local_fastpix` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this
project follows [Semantic Versioning](https://semver.org/).

## [1.1.0] — 2026-06-10

Course-aware uploads and a course-context permission fix.

### Added
- `courseid` is recorded on each upload session (new column on
  `local_fastpix_upload_session`), stamped when the upload starts, so the
  editor video picker can list a teacher's videos scoped to the current
  course. `upload_service::list_ready_for_course()` returns a user's ready,
  non-DRM videos for a course.

### Changed
- The upload web services — `create_upload_session`,
  `create_url_pull_session`, and `get_upload_status` — now take a required
  `contextid` and authorise `mod/fastpix:uploadmedia` against the course
  context (`validate_context()` plus `require_capability()` on the course
  context resolved with `get_course_context()`) instead of the system
  context.

### Fixed
- Editing teachers were wrongly denied upload: the capability was checked at
  the system context, where the course-level `mod/fastpix:uploadmedia` never
  grants, so only site administrators succeeded (by bypassing capability
  checks). Permission is now evaluated at the course context — enrolled
  editing teachers can upload, and enrolled students are correctly blocked
  from uploading or embedding while still able to view.

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
- Public assets resolve tokenless: `playback_service::resolve()` skips
  JWT minting for `access_policy=public`, and the playback payload now
  carries `access_policy` so consumers render public players without a
  token attribute.
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
