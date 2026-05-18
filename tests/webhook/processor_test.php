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

namespace local_fastpix\webhook;

defined('MOODLE_INTERNAL') || die();

// The processor delegates to verifier::verify() which accepts the legacy.
// Raw-string secret + hex output format only when LOCAL_FASTPIX_DEBUG_VERIFIER.
// Is defined. Tests opt in.
defined('LOCAL_FASTPIX_DEBUG_VERIFIER') || define('LOCAL_FASTPIX_DEBUG_VERIFIER', true);

/**
 * Unit tests for the extracted webhook processor.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class processor_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_webhook_event';
    /** @var string */
    private const SECRET =
        'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        verifier::reset();
        set_config('webhook_secret_current', self::SECRET, 'local_fastpix');
        set_config('webhook_secret_previous', '', 'local_fastpix');
        set_config('webhook_secret_rotated_at', 0, 'local_fastpix');
    }

    public function tearDown(): void {
        parent::tearDown();
        verifier::reset();
    }

    /**
     * Helper: build event payload.
     *
     * @param ?string $eventid
     * @return string
     */
    private function build_event_payload(?string $eventid = null): string {
        return json_encode([
            'id'         => $eventid ?? ('evt-' . random_string(8)),
            'type'       => 'video.media.created',
            'occurredAt' => time(),
            'object'     => ['type' => 'video.media', 'id' => 'media-' . random_string(6)],
            'data'       => (object)['title' => 'fixture'],
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Helper: sign.
     *
     * @param string $payload
     * @param string $secret
     * @return string
     */
    private function sign(string $payload, string $secret = self::SECRET): string {
        // Use the legacy raw-string + hex output format that verifier accepts.
        // When LOCAL_FASTPIX_DEBUG_VERIFIER is defined (test mode).
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Test that process accepts valid signed event.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_accepts_valid_signed_event(): void {
        global $DB;
        $payload = $this->build_event_payload('evt-happy-1');
        $result = processor::process($payload, $this->sign($payload));

        $this->assertSame(processor::RESULT_ACCEPTED, $result['result']);
        $this->assertNull($result['error']);
        $this->assertIsInt($result['ledger_id']);
        $this->assertTrue($DB->record_exists(self::TABLE, [
        'provider_event_id' => 'evt-happy-1',
        'status'            => 'pending',
        ]));
    }

    /**
     * Test that process rejects bad signature.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_rejects_bad_signature(): void {
        global $DB;
        $payload = $this->build_event_payload('evt-bad-sig');
        $bad = $this->sign($payload, str_repeat('z', 64));

        $result = processor::process($payload, $bad);

        $this->assertSame(processor::RESULT_BAD_SIGNATURE, $result['result']);
        $this->assertNull($result['ledger_id']);
        $this->assertFalse($DB->record_exists(
            self::TABLE,
            ['provider_event_id' => 'evt-bad-sig']
        ));
    }

    /**
     * Test that process rejects empty signature.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_rejects_empty_signature(): void {
        $payload = $this->build_event_payload();
        $result = processor::process($payload, '');
        $this->assertSame(processor::RESULT_BAD_SIGNATURE, $result['result']);
        $this->assertDebuggingCalled('webhook signature verify: empty body or signature');
    }

    /**
     * Test that process rejects non json body.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_rejects_non_json_body(): void {
        $payload = 'not json at all';
        $result = processor::process($payload, $this->sign($payload));
        $this->assertSame(processor::RESULT_MALFORMED_BODY, $result['result']);
    }

    /**
     * Test that process rejects event missing id.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_rejects_event_missing_id(): void {
        $payload = json_encode([
        'type'   => 'video.media.created',
        'object' => ['type' => 'video.media', 'id' => 'media-x'],
        ]);
        $result = processor::process($payload, $this->sign($payload));
        $this->assertSame(processor::RESULT_MALFORMED_BODY, $result['result']);
    }

    /**
     * Test that process rejects event missing type.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_rejects_event_missing_type(): void {
        $payload = json_encode([
        'id'     => 'evt-no-type',
        'object' => ['type' => 'video.media', 'id' => 'media-x'],
        ]);
        $result = processor::process($payload, $this->sign($payload));
        $this->assertSame(processor::RESULT_MALFORMED_BODY, $result['result']);
    }

    /**
     * Test that process returns duplicate on resubmitted event id.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_process_returns_duplicate_on_resubmitted_event_id(): void {
        $payload = $this->build_event_payload('evt-dup-1');
        $sig = $this->sign($payload);

        $first = processor::process($payload, $sig);
        $this->assertSame(processor::RESULT_ACCEPTED, $first['result']);

        $second = processor::process($payload, $sig);
        $this->assertSame(processor::RESULT_DUPLICATE, $second['result']);
        $this->assertSame($first['ledger_id'], $second['ledger_id']);
    }

    /**
     * Rule W1: 200 unique event_ids each submitted twice in random order
     * yields exactly 200 ledger rows.
     *
     * @covers \local_fastpix\webhook\processor
     */
    public function test_flood_with_50pct_duplicates_yields_unique_count(): void {
        global $DB;
        $unique = 200;
        $ids = [];
        for ($i = 0; $i < $unique; $i++) {
            $ids[] = 'evt-flood-' . $i;
        }
        $duplicated = array_merge($ids, $ids);
        shuffle($duplicated);

        $accepted = 0;
        $duplicates = 0;
        foreach ($duplicated as $eid) {
            $payload = $this->build_event_payload($eid);
            $result = processor::process($payload, $this->sign($payload));
            if ($result['result'] === processor::RESULT_ACCEPTED) {
                $accepted++;
            } else if ($result['result'] === processor::RESULT_DUPLICATE) {
                $duplicates++;
            }
        }

        $this->assertSame($unique, $accepted);
        $this->assertSame($unique, $duplicates);
        $this->assertSame($unique, $DB->count_records(self::TABLE));
    }
}
