# firebase/php-jwt vendored

- Version: v6.10.0
- Source: https://github.com/firebase/php-jwt/releases/tag/v6.10.0
- Tarball: https://github.com/firebase/php-jwt/archive/refs/tags/v6.10.0.tar.gz
- Tarball SHA256: de7ce1ed7502bef3f03d682179ab26a50dde7a4a628db52e03df48047bb24f26
- License: BSD-3-Clause
- Vendored on: 2026-05-04 by @security-compliance
- Consumed by: \local_fastpix\service\jwt_signing_service

## File SHA256

- JWT.php: c1b97ba8fbf2e922e5cdeac5b145410a22485455ee49b3ea5892616a3d773ac7
- Key.php: 0b5c499ca2fc7103cf50118647626f02c10806f05f76d58cb29a58101b549ff8
- JWK.php: ad397a81e4c4562e2a638d6f81b0c7b1abe4d9dd5a57996bea53fe8220396539
- BeforeValidException.php: 77fe003a44eb941bfd2fac7399f8ca761511a2ad49117f093ef12489b6f09cd0
- ExpiredException.php: ead9a40f09beb5c237cfdc4856db3ae88367bd230ed5a1b7d4b242169c08ad85
- SignatureInvalidException.php: fc624b17bf730387079c3b25dd282743259bd3c6c20920e007879412c85ebdee

## DO NOT MODIFY

This directory is vendored third-party code. **NEVER modify these files.**
Any change — even a whitespace-only edit — invalidates the SHA256 attestation
above and breaks the audit trail required by `@security-compliance`.

For upgrades, re-vendor following the steps below. Do not patch in place.

## Re-vendoring

1. Download the new release tarball from
   `https://github.com/firebase/php-jwt/archive/refs/tags/<version>.tar.gz`.
2. Verify tarball SHA256 against the GitHub release page.
3. Compare diffs against this version (read CHANGELOG, scan for new
   algorithms, deprecations, or behavioral changes around RS256).
4. Replace the six PHP files in this directory with the new copies — only the
   six files listed above; no tests, no examples, no `composer.json`.
5. Recompute SHA256 of each file (`shasum -a 256 *.php`) and update the
   "File SHA256" section above.
6. Update the version, source, tarball URL, tarball SHA256, and vendoring
   date at the top of this file.
7. Run the JWT signing roundtrip test (`tests/jwt_signing_service_test.php`).
8. Run the redaction canary test.
9. Obtain `@security-compliance` sign-off before merge (PR-20).

## Why vendored?

Moodle Plugins Directory disallows runtime Composer dependencies (rule M12).
The library is small (six files) and pinned (rotation is rare), so vendoring
is cheaper than building a custom dependency loader.
