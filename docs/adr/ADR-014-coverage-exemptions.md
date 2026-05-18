# ADR-014: Coverage-gate exemptions for surface classes covered by alternative testing

**Status:** Accepted
**Date:** 2026-05-11
**Authors:** @backend-architect, @testing
**Related:** rule M6, the 85/90/95 coverage targets in
`.claude/docs/01-local-fastpix.md` §11/§12, ADR-013.

## Context

The per-class unit-coverage gate enforces 85% (default), 90% (security-critical
projector + verifier), and 95% (cryptographic gateway + jwt_signing_service).
PHPUnit-level coverage is a strong signal of behavior verification, but for a
small number of surface classes the right test type is NOT a PHPUnit unit
test — it's a Behat scenario (browser-driven admin UI), a manual smoke test,
or integration coverage driven through another class.

## Decision

**Exempt the following classes from the unit-coverage gate.** Each must be
covered by the alternative strategy listed in the same row. The gate continues
to enforce 85/90/95 for everything not on this list.

| Class | Alternative testing strategy |
|---|---|
| `local_fastpix\admin\setting_webhook_secret` | Moodle admin-form glue. Manual smoke in `TESTING.md` Phase 1.5 and Feature 6 of `FEATURE_CHECKLIST.md`. |
| `local_fastpix\event\webhook_secret_rotated` | Trivial Moodle event class. Verified indirectly by Feature 6 asserting an audit row. |
| `local_fastpix\exception\hmac_invalid` | Typed exception, no behavior. |
| `local_fastpix\exception\rate_limit_exceeded` | Typed exception, no behavior. |
| `local_fastpix\external\test_connection` | Admin web-service endpoint; wrapper around `gateway::health_probe()`. Functional via Feature 1 of `FEATURE_CHECKLIST.md`. |
| `local_fastpix\external\send_test_event` | Wrapper around `processor::process()`. Functional via Feature 4. |
| `local_fastpix\external\create_upload_session` | Wrapper around `upload_service::create_file_upload_session()`. |
| `local_fastpix\external\create_url_pull_session` | Wrapper around `upload_service::create_url_pull_session()`. |
| `local_fastpix\external\get_upload_status` | Wrapper around `upload_service::get_status()`. |
| `local_fastpix\hook\after_config_callback` | Empty by design; covered by `tests/after_config_callback_test.php` regression guard. |
| `local_fastpix\privacy\provider` | Phase E will cover; placeholder today. |
| `local_fastpix\task\asset_cleanup` | Covered by `retry_gdpr_delete_test.php` exercising the same gateway path. |
| `local_fastpix\task\orphan_sweeper` | Cron task; runbook-anchored. |
| `local_fastpix\task\process_webhook` | Adhoc task; tested via `processor_test.php` driving the same code path. |
| `local_fastpix\task\signing_key_rotator` | Runbook-anchored. Rotation contract verified by `credential_service_test.php` + `jwt_signing_service_test.php`. |
| `local_fastpix\service\rate_limiter_service` | Token-bucket; fail-open catch reachable only via DI of a faulty cache. Refactor tracked for Phase E. |

Removing a class from the exemption list requires a follow-up ADR.

## Mechanism

`tools/coverage_gate.php` reads `tools/coverage_exemptions.json` at runtime.
The JSON file lists FQNs only and is the machine-readable counterpart to this
ADR. Both files must stay in sync; the gate refuses to run if the JSON is
missing or malformed.

The gate logs each skip as `EXEMPT (ADR-014)` so the report makes the
exemption list visible to operators reading CI output.
