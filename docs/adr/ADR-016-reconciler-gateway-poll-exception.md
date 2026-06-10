# ADR-016: A scheduled reconciler may poll FastPix (bounded W7/A5 exception)

**Status:** Accepted
**Date:** 2026-06-09

## Context

Rules **A5** and **W7** forbid gateway calls from write paths and scheduled
tasks: the projector must not make HTTP, and scheduled tasks must use
`asset_service::get_by_fastpix_id` (no `_or_fetch`). The rationale is the
outage feedback loop — lazy fetch from a write path during a FastPix outage
amplifies load and prolongs the outage.

But FastPix webhooks can be **missed** (a dropped delivery, an unreachable
tunnel, a provider hiccup). When the `video.media.ready` event never arrives,
the asset is stuck non-terminal locally forever even though FastPix finished —
the video shows "Preparing" indefinitely. There is no self-heal for an asset
that already exists in the DB (lazy fetch only covers cold-start, not stuck
rows). This was observed live (a real upload ready on FastPix for minutes with
no local update).

## Decision

Introduce one — and only one — scheduled task that polls FastPix:
`\local_fastpix\task\reconcile_pending_assets`. It finds assets stuck in a
non-terminal status past a threshold, calls `gateway::get_media()` explicitly
(NOT `_or_fetch`), and projects the authoritative result through the existing
projector (ready/failed). This is a bounded exception to W7/A5, justified
because reconciliation is the express purpose of the task and the alternative
is permanently stuck videos.

A5 is preserved literally: the **projector still makes no HTTP**. The reconciler
fetches, then hands the data to the projector as a synthetic event — the
projector only applies events, exactly as for a webhook.

## Guards (mandatory)

To honour the spirit of W7/A5 (no outage amplification):

1. **Circuit-breaker aware.** `get_media` throws `gateway_unavailable`
   (including `circuit_open:*`) when the breaker is open. On that exception the
   task **stops the whole run** and defers to the next schedule — it never
   keeps polling during an outage.
2. **Batched.** At most `BATCH_SIZE` (50) assets per run.
3. **Time-boxed.** Stops after `TIME_BUDGET_SECONDS` (60s), deferring the rest.
4. **Threshold.** Only assets untouched for `STUCK_AFTER_SECONDS` (30 min) are
   eligible, so it never races a normally-arriving webhook.
5. **Explicit, not lazy.** It calls `gateway::get_media` directly; the
   `_or_fetch` lazy-fetch path remains banned everywhere.

## Consequences

- The CI grep for A5 (`classes/webhook/` must not reference `gateway`/`_or_fetch`)
  is unaffected — the reconciler lives in `classes/task/`, like the other
  gateway-using tasks (`orphan_sweeper`, `asset_cleanup`, `retry_gdpr_delete`).
- Missed-webhook recovery becomes automatic within one reconcile cycle.
- The poll rate is bounded by the batch cap, time-box, and breaker, so a FastPix
  outage cannot be amplified by this task.
