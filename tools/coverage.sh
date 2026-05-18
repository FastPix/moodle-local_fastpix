#!/usr/bin/env bash
# Coverage gate for local_fastpix.
#
# Runs the PHPUnit test suite under pcov, emits a clover-format coverage
# report at build/coverage.xml, then invokes tools/coverage_gate.php to
# enforce the per-class architecture targets:
#
#   gateway              95%
#   jwt_signing_service  95%
#   verifier             90%
#   projector            90%
#   all other classes    85%
#
# Exits 0 if every class meets its target. Exits 1 with a remediation
# report listing every shortfall otherwise.
#
# Usage from the plugin root:
#   bash tools/coverage.sh
#
# Or in CI: see .github/workflows/moodle-plugin-ci.yml.

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
BUILD_DIR="${PLUGIN_DIR}/build"
CLOVER_PATH="${BUILD_DIR}/coverage.xml"

mkdir -p "${BUILD_DIR}"

# Resolve the Moodle root by trying known candidate paths in order, since
# the layout differs between local dev and moodle-plugin-ci CI runs:
#   1. $MOODLE_DIR env var (moodle-plugin-ci exports this in some configs)
#   2. $PLUGIN_DIR/../..    — local dev: plugin lives at moodle/local/fastpix
#   3. $PLUGIN_DIR/../moodle — CI: plugin is checked out under workspace
#      root; moodle-plugin-ci installs Moodle into a sibling `moodle/` dir
#   4. $GITHUB_WORKSPACE/moodle — GitHub Actions explicit fallback
declare -a candidates=()
[[ -n "${MOODLE_DIR:-}" ]]        && candidates+=("${MOODLE_DIR}")
candidates+=("${PLUGIN_DIR}/../..")
candidates+=("${PLUGIN_DIR}/../moodle")
[[ -n "${GITHUB_WORKSPACE:-}" ]]  && candidates+=("${GITHUB_WORKSPACE}/moodle")

MOODLE_ROOT=""
for cand in "${candidates[@]}"; do
    if [[ -x "${cand}/vendor/bin/phpunit" ]]; then
        MOODLE_ROOT="$(cd "${cand}" && pwd -P)"
        break
    fi
done

if [[ -z "${MOODLE_ROOT}" ]]; then
    echo "coverage.sh: vendor/bin/phpunit not found in any candidate path:" >&2
    for cand in "${candidates[@]}"; do
        echo "  - ${cand}" >&2
    done
    echo "Run from a properly bootstrapped Moodle install with phpunit configured." >&2
    exit 2
fi

echo "coverage.sh: running phpunit with coverage..."
# pcov.enabled defaults to 0 on many builds (including the moodle-docker
# webserver image); pass it as a CLI ini override so we don't need to
# touch the php.ini in the runner.
(
    cd "${MOODLE_ROOT}"
    php -d pcov.enabled=1 vendor/bin/phpunit \
        --testsuite=local_fastpix_testsuite \
        --coverage-clover="${CLOVER_PATH}" 2>&1 \
        | tail -20
)

if [[ ! -s "${CLOVER_PATH}" ]]; then
    echo "coverage.sh: clover report not generated at ${CLOVER_PATH}" >&2
    exit 1
fi

echo "coverage.sh: enforcing per-class targets..."
php "${PLUGIN_DIR}/tools/coverage_gate.php" "${CLOVER_PATH}"
