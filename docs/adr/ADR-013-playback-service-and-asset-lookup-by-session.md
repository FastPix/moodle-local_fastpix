# ADR-013: Add playback_service + asset_service::get_by_upload_session_id

**Status:** Accepted
**Date:** 2026-05-08
**Authors:** @backend-architect, @local-fastpix-contract (routed from mod_fastpix Phase C kickoff)

## Context

mod_fastpix Phase C requires obtaining a playback JWT to render
`<fastpix-player>` on view.php. The documented consumed surface lists:

  - \local_fastpix\service\playback_service::resolve(...) returning
    playback_payload DTO (playback_id, jwt, expires_at_ts,
    drm_required, watermark_html, tracking_enabled)

This class does not exist in local_fastpix today. The only JWT-minting
path is \local_fastpix\service\jwt_signing_service::sign_for_playback,
which mod_fastpix is forbidden from importing (A4 / PR-3).

Separately, mod_fastpix Phase B persists upload_session_id on the
activity row but leaves fastpix_asset_id NULL. The webhook later
creates the asset row in mdl_local_fastpix_asset. Phase C view.php
needs to resolve upload_session_id → asset, but no documented method
takes upload_session_id as input.

## Decision

Add two methods to local_fastpix's public service layer. Both are
additive; no existing surface changes.

### 1. `\local_fastpix\service\playback_service::resolve`

Signature:
  resolve(string $fastpix_id, int $userid): playback_payload

Returns the playback_payload DTO.

Internally:
  1. asset_service::get_by_fastpix_id($fastpix_id) — confirm asset
     exists, is ready, is not soft-deleted.
  2. jwt_signing_service::sign_for_playback($asset->playback_id) —
     mint the JWT.
  3. Build and return the DTO.

Throws asset_not_found if missing/soft-deleted, asset_not_ready if
status != 'ready'.

### 2. `\local_fastpix\service\asset_service::get_by_upload_session_id`

Signature:
  get_by_upload_session_id(int $session_id): ?\stdClass

Returns the asset DTO bound to the upload session, or null if no
asset has been created yet. Same caching contract as
get_by_fastpix_id (MUC + DB fallback). Filters out soft-deleted assets.

Internally reads mdl_local_fastpix_upload_session by id; if fastpix_id
is empty returns null; otherwise delegates to get_by_fastpix_id.

## Why

Without playback_service, the consumer-contract document is fictional
and mod_fastpix Phase C ships with a PR-3 violation baked in. Adding
it makes the documented surface real. The asset_service lookup closes
the Phase B handoff gap without making mod_fastpix read the upload
session table directly.

## Cost

~80 LOC across two services + one new DTO method. Tests ~40 LOC.
Version bump in local_fastpix.

## Routing

Implementation routed to @asset-service + @jwt-signing.
