# ADR-015: `create_upload_session` returns the integer `session_id`

**Status:** Accepted
**Date:** 2026-06-09

## Context

`local_fastpix_create_upload_session` and `local_fastpix_create_url_pull_session`
both create a `local_fastpix_upload_session` row, but historically returned
asymmetric shapes:

- `create_url_pull_session` → `{ session_id, upload_id, upload_url, expires_at, deduped }`.
- `create_upload_session` → `{ uploadurl, uploadid }` only (after the
  settings-acceptance task narrowed its return to the two fields the browser
  resumable-upload SDK needs).

The consumer (`mod_fastpix`) stores the upload reference in
`mdl_fastpix.upload_session_id`, a bigint FK to `local_fastpix_upload_session.id`,
cleaned with `PARAM_INT`. With no integer `session_id` in the response, the
consumer fell back to the FastPix UUID `uploadid`, which `PARAM_INT` truncated:

```
uploadid  "829f5215-0451-4d39-8638-5c2947d22cb7"  ->  upload_session_id = 829
```

`asset_service::get_by_upload_session_id(829)` then finds nothing, and
`playback_service::resolve_for_view()` is stuck on the processing state forever.
Direct-upload videos never play; URL-pull videos (which return `session_id`) do.

The service layer already persists the session row and already returns
`session_id` via `build_response()`. Only the external function dropped it.

## Decision

Add `session_id` (`PARAM_INT`) to `create_upload_session`'s `execute_returns`
and return it from `execute()`. This restores symmetry with
`create_url_pull_session`. `uploadurl` and `uploadid` are unchanged — the
resumable-upload SDK PUTs to `uploadurl`, and the consumer prefers `session_id`
but falls back to `uploadid`.

This is an **additive, backward-compatible** change to a consumed external
function. Per the plugin contract, interface drift on `upload_service` / its
external functions requires an ADR; this is it.

## Consequences

- The webhook path already links the session to the asset
  (`projector::link_upload_session()` matches `upload_session.upload_id ==
  asset.fastpix_id`, the shared FastPix UUID) and stamps the owner from
  `upload_session.userid` (`backfill_owner_from_session()`), so once the
  consumer stores the integer `session_id`, `get_by_upload_session_id()`
  resolves and playback proceeds. No projector change is required.
- **Both plugins ship together.** Until `session_id` is returned, direct-upload
  playback stays broken; the `mod_fastpix` consumer already reads
  `session.session_id || session.uploadid`, so no consumer change is needed once
  this ships.
- `version.php` is bumped to `2026061001` to mark the paired release.
