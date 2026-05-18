# ADR-012: Capability ownership for `:uploadmedia`

**Status:** Accepted
**Date:** 2026-05-05

## Decision

Keep `mod/fastpix:uploadmedia` in `mod_fastpix`. `local_fastpix` continues to define exactly one capability: `local/fastpix:configurecredentials`.

## Why

Senior code review (`docs/review/REVIEW-2026-05-04.md` §7) predicted install would fail if `mod_fastpix` is absent. Empirical test on 2026-05-05 (PHPUnit init log) disproved this — Moodle calls `debugging()` for missing capability references, not fatal. Install completes successfully (`++ Success ++`).

Architecture doc §5 says `local_fastpix` defines exactly one capability. The `@security-compliance` agent guardrail says "refuse to define new capabilities." With the install-failure prediction disproven, both authorities stand.

## Cost

A `debugging()` warning appears on every install where `mod_fastpix` is absent. Acceptable cost for architectural consistency. Resolves automatically when `mod_fastpix` ships (Phase 2).

## What this means for the TODO

T0.4 ("Apply T0.3 decision in code") is **skipped**. No code change needed. No version bump for this reason.
