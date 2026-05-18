<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Coverage gate parser.
 * Reads a phpunit clover-format coverage report and enforces the per-class
 * architecture targets from .claude/docs/01-local-fastpix.md §11/§12 and
 * the project's coverage policy:
 *   gateway              95%
 *   jwt_signing_service  95%
 *   verifier             90%
 *   projector            90%
 *   all other classes    85%
 * Exits 0 if every class meets its target.
 * Exits 1 with a remediation report (one line per shortfall) otherwise.
 * Exits 2 on report-parse failure.
 * Invoked by tools/coverage.sh; not meant to be run standalone.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var float Default coverage target (percent) for any class not in the overrides table. */
const TARGET_DEFAULT = 85.0;

/** @var array<string,float> Per-class coverage overrides (FQCN => percent). */
const TARGET_OVERRIDES = [
    'local_fastpix\\api\\gateway'                    => 95.0,
    'local_fastpix\\service\\jwt_signing_service'    => 95.0,
    'local_fastpix\\webhook\\verifier'               => 90.0,
    'local_fastpix\\webhook\\projector'              => 90.0,
];

// Files under classes/vendor/ are excluded from coverage entirely; this.
// List is a safety net if the phpunit.xml exclude doesn't take effect.
/** Constant. */
const EXCLUDE_FILE_PATTERNS = [
    '#/classes/vendor/#',
];

// PHPUnit emits coverage for every class that ran, not just the plugin's.
// Restrict the gate to our namespace; Moodle core / other plugins are.
// Out of scope for this enforcement.
/** Constant. */
const NAMESPACE_PREFIX = 'local_fastpix\\';

if ($argc < 2) {
    fwrite(STDERR, "usage: php coverage_gate.php <clover.xml>\n");
    exit(2);
}

// Load ADR-014 exemption list.
$exemptionspath = __DIR__ . '/coverage_exemptions.json';
if (!is_readable($exemptionspath)) {
    fwrite(STDERR, "coverage_gate: missing exemption list at {$exemptionspath}\n");
    fwrite(STDERR, "  Required by ADR-014. Refusing to run rather than silently pass.\n");
    exit(2);
}
$exemptions = json_decode((string)file_get_contents($exemptionspath), true);
if (!is_array($exemptions) || !isset($exemptions['exempt_classes']) || !is_array($exemptions['exempt_classes'])) {
    fwrite(STDERR, "coverage_gate: malformed exemption list at {$exemptionspath}\n");
    exit(2);
}
$exempt = array_flip(array_map(
    static fn($fqn) => ltrim((string)$fqn, '\\'),
    $exemptions['exempt_classes']
));

$cloverpath = $argv[1];
if (!is_readable($cloverpath)) {
    fwrite(STDERR, "coverage_gate: cannot read {$cloverpath}\n");
    exit(2);
}

$prev = libxml_use_internal_errors(true);
$doc = simplexml_load_file($cloverpath);
$errors = libxml_get_errors();
libxml_clear_errors();
libxml_use_internal_errors($prev);

if ($doc === false || !empty($errors)) {
    fwrite(STDERR, "coverage_gate: clover XML failed to parse\n");
    foreach ($errors as $e) {
        fwrite(STDERR, '  - ' . trim($e->message) . "\n");
    }
    exit(2);
}

$shortfalls = [];
$passes = [];
$exempted = [];

foreach ($doc->project->file ?? [] as $file) {
    $filename = (string)$file['name'];

    foreach (EXCLUDE_FILE_PATTERNS as $pattern) {
        if (preg_match($pattern, $filename)) {
            continue 2;
        }
    }

    foreach ($file->class ?? [] as $class) {
        $namespace = (string)($class['namespace'] ?? '');
        $name      = (string)$class['name'];

        // Pcov clover writes the FQN in the `name` attribute and uses.
        // `namespace="global"` as a literal placeholder regardless of.
        // The actual PHP namespace. Treat `name` as authoritative when.
        // It already contains a backslash; otherwise concatenate.
        if (str_contains($name, '\\')) {
            $fqn = $name;
        } else {
            $fqn = $namespace !== '' && $namespace !== 'global'
                ? "{$namespace}\\{$name}"
                : $name;
        }

        // Restrict to local_fastpix classes. PHPUnit reports coverage.
        // For every class that ran (Moodle core helpers, vendor libs,.
        // Etc.); enforcing targets on those is out of scope.
        if (!str_starts_with($fqn, NAMESPACE_PREFIX)) {
            continue;
        }

        // ADR-014 exemption — record but do not gate.
        if (isset($exempt[$fqn])) {
            $exempted[] = $fqn;
            continue;
        }

        $metrics = $class->metrics ?? null;
        if ($metrics === null) {
            continue;
        }
        $statements = (int)($metrics['statements'] ?? 0);
        $covered    = (int)($metrics['coveredstatements'] ?? 0);

        if ($statements === 0) {
            // Pure-data class (DTOs, exceptions). Nothing to cover.
            continue;
        }

        $coveragepct = ($covered / $statements) * 100.0;
        $target = TARGET_OVERRIDES[$fqn] ?? TARGET_DEFAULT;

        if ($coveragepct + 0.0001 < $target) {
            $shortfalls[] = sprintf(
                '  %-55s %5.1f%% (target %5.1f%%, short %4.1f pp, %d/%d statements)',
                $fqn,
                $coveragepct,
                $target,
                $target - $coveragepct,
                $covered,
                $statements,
            );
        } else {
            $passes[] = sprintf(
                '  %-55s %5.1f%% (target %5.1f%%, %d/%d statements)',
                $fqn,
                $coveragepct,
                $target,
                $covered,
                $statements,
            );
        }
    }
}

echo "\n== Coverage gate ==\n";
echo "Passes (" . count($passes) . "):\n";
foreach ($passes as $p) {
    echo $p . "\n";
}

if (!empty($exempted)) {
    echo "\nExempt (" . count($exempted) . ") — ADR-014 coverage-exemptions:\n";
    foreach ($exempted as $fqn) {
        echo "  EXEMPT (ADR-014)  {$fqn}\n";
    }
}

if (empty($shortfalls)) {
    echo "\nAll non-exempt classes meet their coverage targets.\n";
    exit(0);
}

echo "\nShortfalls (" . count($shortfalls) . "):\n";
foreach ($shortfalls as $s) {
    echo $s . "\n";
}
echo "\nRemediation: add tests until every shortfall reaches its target.\n";
echo "Targets are non-negotiable per architecture; do not lower the bar.\n";
exit(1);
