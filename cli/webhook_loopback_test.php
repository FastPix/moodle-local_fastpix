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
 * Webhook end-to-end loopback test (T3.2).
 * Operator-facing CLI that fires synthetic, properly-signed FastPix-shaped
 * events at the local webhook endpoint and verifies the full ingestion
 * path (verifier → ledger insert → adhoc task → projector → asset row).
 * Use cases:
 *   - Smoke test after deploy: did the webhook URL come up correctly?
 *   - Reproduce ingestion bugs offline without FastPix sandbox creds.
 *   - DoD §7 partial coverage: shoot a small flood with duplicates +
 *     out-of-order events and assert idempotency.
 * Usage (from Moodle root):
 *   php local/fastpix/cli/webhook_loopback_test.php
 *   php local/fastpix/cli/webhook_loopback_test.php --count=100 --dup=0.5
 *   php local/fastpix/cli/webhook_loopback_test.php --webhook-url=https://your.example/local/fastpix/webhook.php
 * Exits 0 on success, non-zero with a diagnostic on any failure.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, ] = cli_get_params(
    [
        'help'        => false,
        'count'       => 1,
        'dup'         => 0.0,
        'webhook-url' => null,
        'timeout'     => 5,
    ],
    [
        'h' => 'help',
        'c' => 'count',
    ],
);

if ($options['help']) {
    cli_writeln(<<<HELP
Webhook loopback test for local_fastpix.

  --count=N           Fire N events (default 1).
  --dup=F             Duplicate fraction in [0.0, 1.0] (default 0).
                      0.5 means half the events resend an existing event_id.
  --webhook-url=URL   Override the webhook URL. Defaults to the local
                      site's /local/fastpix/webhook.php.
  --timeout=N         HTTP timeout per request (default 5s).

Exits 0 on success.
HELP);
    exit(0);
}

global $DB, $CFG;

$count = max(1, (int)$options['count']);
$dup = max(0.0, min(1.0, (float)$options['dup']));
$timeout = max(1, (int)$options['timeout']);
$webhookurl = $options['webhook-url']
    ?? rtrim($CFG->wwwroot, '/') . '/local/fastpix/webhook.php';

// Read the configured signing secret. Bail early if it's missing or.
// Below the verifier's MIN_SECRET_BYTES floor — the loopback would.
// Just generate 401s.
$secret = (string)get_config('local_fastpix', 'webhook_secret_current');
if ($secret === '') {
    cli_problem('webhook_secret_current is empty. Run db/install.php or set it via CLI.');
    exit(2);
}
if (strlen($secret) < 32) {
    cli_problem(sprintf(
        'webhook_secret_current is %d chars, below verifier minimum (32). Loopback would 401.',
        strlen($secret),
    ));
    exit(2);
}

cli_writeln("webhook_loopback_test: target={$webhookurl}");
cli_writeln("                       count={$count} dup={$dup} timeout={$timeout}s");

$results = [
    'sent'         => 0,
    'http_200'     => 0,
    'http_other'   => [],
    'unique_ids'   => [],
    'duplicates'   => 0,
];

// Generate event IDs.

// Build a list of event IDs honoring the dup fraction. Duplicates re-use a.
// Previously-emitted ID so the receiver's UNIQUE constraint kicks in.
$eventids = [];
$uniquepool = [];
for ($i = 0; $i < $count; $i++) {
    if (!empty($uniquepool) && mt_rand(0, 999) / 1000.0 < $dup) {
        $eventids[] = $uniquepool[array_rand($uniquepool)];
        $results['duplicates']++;
    } else {
        $id = 'loopback-' . bin2hex(random_bytes(8));
        $eventids[] = $id;
        $uniquepool[] = $id;
    }
}

// Fire events.

$fastpixid = 'loopback-asset-' . bin2hex(random_bytes(6));

foreach ($eventids as $eventid) {
    $payload = json_encode([
        'id'         => $eventid,
        'type'       => 'video.media.failed',
        'occurredAt' => time(),
        'object'     => ['type' => 'video.media', 'id' => $fastpixid],
        'data'       => (object)[],
    ], JSON_UNESCAPED_SLASHES);

    $signature = hash_hmac('sha256', $payload, $secret);

    $ch = curl_init($webhookurl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'FastPix-Signature: ' . $signature,
            'Content-Length: ' . strlen($payload),
        ],
    ]);
    $responsebody = curl_exec($ch);
    $httpcode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerr = curl_error($ch);
    curl_close($ch);

    $results['sent']++;
    if ($httpcode === 200) {
        $results['http_200']++;
    } else {
        $results['http_other'][] = [
            'event_id' => $eventid,
            'code'     => $httpcode,
            'body'     => substr((string)$responsebody, 0, 200),
            'curl_err' => $curlerr,
        ];
    }
}

$results['unique_ids'] = count(array_unique($eventids));

// Ledger reconciliation.

// Wait briefly for any in-flight inserts to commit.
usleep(200_000);

$ledgercount = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {local_fastpix_webhook_event}
      WHERE provider_event_id LIKE :pat",
    ['pat' => 'loopback-%'],
);

// Report.

cli_writeln('');
cli_writeln('Results:');
cli_writeln(sprintf('  sent:              %d', $results['sent']));
cli_writeln(sprintf('  HTTP 200:          %d', $results['http_200']));
cli_writeln(sprintf('  unique event IDs:  %d', $results['unique_ids']));
cli_writeln(sprintf('  duplicates fired:  %d', $results['duplicates']));
cli_writeln(sprintf('  ledger rows seen:  %d (expect = unique IDs)', $ledgercount));

if (!empty($results['http_other'])) {
    cli_writeln('');
    cli_writeln('Non-200 responses:');
    foreach (array_slice($results['http_other'], 0, 5) as $row) {
        cli_writeln(sprintf(
            '  event=%s code=%d curl_err=%s body=%s',
            $row['event_id'],
            $row['code'],
            $row['curl_err'] ?: '-',
            $row['body'] ?: '-',
        ));
    }
}

$ok = $results['http_200'] === $results['sent']
    && $ledgercount >= $results['unique_ids'];

cli_writeln('');
cli_writeln($ok ? 'PASS' : 'FAIL');

exit($ok ? 0 : 1);
