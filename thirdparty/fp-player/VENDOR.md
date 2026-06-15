# Vendored: @fastpix/fp-player

The FastPix web player, served locally so no JavaScript is loaded from a CDN at
runtime (Moodle Plugins Directory rule; see ADR-017).

| | |
|---|---|
| Package | `@fastpix/fp-player` (npm) |
| Version (pin) | **1.0.17** |
| File | `player.esm.js` |
| Size | 274662 bytes |
| SHA256 | `540882b7dbfed12629e138f129618324035fd17b086525e1c457190ff8a5c0c1` |
| License | MIT (© 2025 FastPix, Inc) — see `LICENSE` |
| Source | `https://registry.npmjs.org/@fastpix/fp-player/-/fp-player-1.0.17.tgz` (`package/dist/player.esm.js`) |

## Notes

- **Do not edit `player.esm.js`.** It is upstream third-party code; modifying it
  breaks the SHA256 above and the upgrade story. It is declared in
  `local/fastpix/thirdpartylibs.xml`, which exempts it from Moodle JS linting.
- It is a **self-contained ESM** loaded via native dynamic `import()` (NOT
  RequireJS/AMD). It must not be moved under `amd/` or processed by `grunt`.
- It contains a **dormant** hls auto-loader URL
  (`https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js`) guarded by
  `window.Hls ? Promise.resolve() : <load cdn>`. Consumers pre-set `window.Hls`
  from their own vendored `hls.js` before importing the player, so that branch
  never loads anything.
- The URL is exposed to consumers via
  `\local_fastpix\service\playback_service::player_lib_url()`.

## Upgrading (explicit code change — ADR-017 / CC9)

1. `curl -L -o pkg.tgz https://registry.npmjs.org/@fastpix/fp-player/-/fp-player-<ver>.tgz`
2. `tar -xzf pkg.tgz` and copy `package/dist/player.esm.js` + `package/LICENSE` here.
3. Update the version, size and SHA256 in this file and the version in
   `thirdpartylibs.xml`.
4. Bump `PLAYER_LIB_VERSION` in `classes/service/playback_service.php` (cache-bust).
5. Bump `$plugin->version` in `version.php`.
6. Re-verify consumers (`mod_fastpix`, `filter_fastpix`) still play back.
