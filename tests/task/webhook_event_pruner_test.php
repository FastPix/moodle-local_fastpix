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

namespace local_fastpix\task;

/**
 * Boundary test for the 90-day webhook ledger retention (rule W9).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class webhook_event_pruner_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_webhook_event';
    /** @var int */
    private const DAY = 86400;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Helper: insert event.
     *
     * @param int $receivedat
     * @param string $status
     * @return int
     */
    private function insert_event(int $receivedat, string $status = 'processed'): int {
        global $DB;
        return (int)$DB->insert_record(self::TABLE, (object)[
            'provider_event_id'     => 'evt-' . random_string(8),
            'event_type'            => 'video.media.created',
            'event_created_at'      => $receivedat,
            'payload'               => '{}',
            'signature'             => 'test-sig',
            'received_at'           => $receivedat,
            'status'                => $status,
            'processing_latency_ms' => 0,
        ]);
    }

    /**
     * Test that prunes at 91 days.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_prunes_at_91_days(): void {
        global $DB;
        $id = $this->insert_event(time() - 91 * self::DAY);
        ob_start();
        (new webhook_event_pruner())->execute();
        ob_end_clean();
        $this->assertFalse($DB->record_exists(self::TABLE, ['id' => $id]));
    }

    /**
     * Test that keeps at 89 days.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_keeps_at_89_days(): void {
        global $DB;
        $id = $this->insert_event(time() - 89 * self::DAY);
        ob_start();
        (new webhook_event_pruner())->execute();
        ob_end_clean();
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $id]));
    }

    /**
     * Test that boundary at 90 days minus 1s kept.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_boundary_at_90_days_minus_1s_kept(): void {
        global $DB;
        $id = $this->insert_event(time() - 90 * self::DAY + 1);
        ob_start();
        (new webhook_event_pruner())->execute();
        ob_end_clean();
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $id]));
    }

    /**
     * Test that boundary at 90 days plus 1s pruned.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_boundary_at_90_days_plus_1s_pruned(): void {
        global $DB;
        $id = $this->insert_event(time() - 90 * self::DAY - 1);
        ob_start();
        (new webhook_event_pruner())->execute();
        ob_end_clean();
        $this->assertFalse($DB->record_exists(self::TABLE, ['id' => $id]));
    }

    /**
     * Test that pending events never pruned even when old.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_pending_events_never_pruned_even_when_old(): void {
        global $DB;
        $id = $this->insert_event(time() - 365 * self::DAY, 'pending');
        ob_start();
        (new webhook_event_pruner())->execute();
        ob_end_clean();
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $id]));
    }

    /**
     * Test that malformed events never pruned even when old.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_malformed_events_never_pruned_even_when_old(): void {
        global $DB;
        $id = $this->insert_event(time() - 365 * self::DAY, 'malformed');
        ob_start();
        (new webhook_event_pruner())->execute();
        ob_end_clean();
        $this->assertTrue($DB->record_exists(self::TABLE, ['id' => $id]));
    }

    /**
     * Test that get name returns lang string.
     *
     * @covers \local_fastpix\task\webhook_event_pruner
     */
    public function test_get_name_returns_lang_string(): void {
        $this->assertSame(
            'Prune old processed webhook events',
            (new webhook_event_pruner())->get_name()
        );
    }
}
