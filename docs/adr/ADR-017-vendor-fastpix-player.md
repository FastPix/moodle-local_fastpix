# ADR-017: Vendor the FastPix web player into local_fastpix and serve it locally

**Status:** Accepted
**Date:** 2026-06-15

## Context

The Moodle Plugins Directory requires that **all JavaScript be served from the
Moodle site** — no runtime loads from a third-party CDN. `mod_fastpix` currently
violates this: it loads the FastPix web player from jsdelivr.

```
mod_fastpix: playback_service::PLAYER_LIB_URL =
  'https://cdn.jsdelivr.net/npm/@fastpix/fp-player@1.0.17/dist/player.esm.js'
```

That URL is exposed to the browser via the `view_state_player` payload field
`player_lib_url` and imported with a **native dynamic `import()`** in
`mod_fastpix/amd/src/player.js`. `player.js` first sets `window.Hls` from the
separately-vendored `hls.js`, *then* imports the player; the player's own
built-in CDN hls auto-loader is therefore dormant.

`mod_fastpix` already vendors the two A3-safe libraries it needs (`hls.js`, the
resumable-uploads SDK). The **player cannot be vendored into `mod_fastpix`**:
`dist/player.esm.js` contains 8 live FastPix endpoint literals
(`api.fastpix.io` ×3, `stream.fastpix.io` ×2, `www.fastpix.io` ×2,
`images.fastpix.io` ×1 — DRM cert/license, manifest, thumbnail and docs
endpoints baked into the build). Those trip `mod_fastpix` rule **A3 / PR-1**
("no `fastpix.io` literals in `mod/fastpix` source", enforced by a CI grep over
the whole tree).

`local_fastpix` is the one plugin that **is allowed** FastPix literals — it owns
the FastPix relationship and is the single trusted boundary (see CLAUDE.md, A2).
So the player belongs here.

### The asset

- npm `@fastpix/fp-player`, pinned **1.0.17**, `dist/player.esm.js` (274,662 bytes).
- License **MIT** (© 2025 FastPix, Inc).
- Self-contained ESM: no top-level imports, no jsdelivr re-imports. The default
  side-effect defines the `<fastpix-player>` custom element
  (`customElements.define("fastpix-player", …)`).
- Loaded via native `import()` (**not** RequireJS/AMD) — must stay that way; it
  is not an AMD module and must not be processed by `grunt`.
- Contains one **dormant** hls auto-loader URL
  (`https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js`) guarded by
  `window.Hls ? Promise.resolve() : <load cdn>`. Because the consumer pre-sets
  `window.Hls`, that branch never loads anything.

## Decision

1. **`local_fastpix` vendors `@fastpix/fp-player@1.0.17` and serves it locally.**
   The file lives at `local/fastpix/thirdparty/fp-player/player.esm.js`, is
   declared in `local/fastpix/thirdpartylibs.xml` (license MIT, version 1.0.17),
   and ships in the release archive (not export-ignored).

2. **The player URL is a documented consumed surface.** `local_fastpix` exposes
   the absolute, `wwwroot`-prefixed URL via a public method on the existing
   consumed `playback_service`:

   ```php
   \local_fastpix\service\playback_service::player_lib_url(): string
   // → "<wwwroot>/local/fastpix/thirdparty/fp-player/player.esm.js?ver=1.0.17"
   ```

   This is the recommended shape; `local_fastpix` owns the surface and the exact
   signature is final per this ADR. Per **CC7** this is a `local_fastpix`
   interface change and must precede any consumer wiring.

3. **Consumers.** `mod_fastpix` and `filter_fastpix` render players and consume
   `player_lib_url()` (replacing `mod_fastpix`'s `PLAYER_LIB_URL` constant).
   `tinymce_fastpix` is the editor "insert video" picker — it emits a shortcode
   and never instantiates a player, so it does **not** consume this surface.

4. **Versioning.** The pin is tracked in `thirdpartylibs.xml` and in a single
   `PLAYER_LIB_VERSION` constant on `playback_service` (also used as the
   cache-bust query string). A player version bump is an **explicit code
   change** — re-vendor the file, update the SHA256 in `thirdparty/fp-player/`,
   bump `thirdpartylibs.xml` and `PLAYER_LIB_VERSION`, bump `version.php`. This
   matches the **CC9** "upstream component is a versioned consumed surface" note
   in `mod_fastpix`.

5. **Native `import()` + `window.Hls` pre-set are preserved.** This ADR changes
   *where the player file is fetched from* (local, not CDN) and nothing else.
   The consumer still imports it with native `import()` and still sets
   `window.Hls` from its own vendored `hls.js` first, so the player's built-in
   CDN hls fallback stays dormant.

## Why `thirdparty/`, not `classes/vendor/` (M12)

Rule **M12** vendors libraries into `classes/vendor/<lib>/`. That clause targets
**PHP** libraries loaded by the autoloader (e.g. `firebase/php-jwt`). The player
is a **browser asset** that must be reachable by an HTTP URL for native
`import()`; the Moodle convention for web-served third-party assets is the
plugin's `thirdparty/` directory, declared in `thirdpartylibs.xml` (which also
exempts it from JS linting). The M12 *spirit* is preserved: a single declared
location, recorded license, pinned version, and a SHA256 provenance record
(`thirdparty/fp-player/VENDOR.md`).

## Consequences

- **No runtime CDN load.** With the player local and `window.Hls` pre-set, no
  JavaScript is fetched from any CDN at runtime — the directory rule is met.
- **Dormant CDN string remains in the third-party file.** The vendored player
  still *contains* the jsdelivr hls URL string. We do **not** patch vendored
  code (it would break the SHA/pin and the upgrade story). It is never executed;
  the dormancy is enforced by the consumer's load order, and the file is
  declared third-party in `thirdpartylibs.xml`.
- **Cache-busting.** The URL carries `?ver=<PLAYER_LIB_VERSION>`; native
  `import()` treats it as a distinct module key, so a version bump invalidates
  the browser/module cache without renaming the file.
- **Archive size.** Adds ~274 KB to the plugin package (one third-party file).
- **CI.** `thirdparty/` is outside `classes/` and `amd/`, so the A2/A5 greps and
  the `grunt` step are unaffected. The `fastpix.io` literals are inside a
  declared third-party file and inside `local_fastpix` (where literals are
  allowed), so A2/PR-1 do not fire.
- **Follow-up (separate, in `mod_fastpix`):** replace `PLAYER_LIB_URL` with a
  call to `playback_service::player_lib_url()` and confirm the last jsdelivr
  reference is gone.
