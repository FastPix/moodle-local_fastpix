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
 * Scheduled task: reconcile stuck assets against FastPix (Issue 2).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

use local_fastpix\exception\gateway_not_found;
use local_fastpix\exception\gateway_unavailable;

/**
 * Missed-webhook backfill. Finds assets stuck in a non-terminal status past a
 * threshold, asks FastPix for their current status, and projects the result
 * (ready / failed) through the existing projector.
 *
 * This is the ONLY task allowed to poll FastPix (ADR-016, a bounded exception
 * to W7/A5). Guards: breaker-aware (stops on gateway_unavailable), batched,
 * time-boxed, and only touching assets untouched for the stuck threshold so it
 * never races a normally-arriving webhook.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reconcile_pending_assets extends \core\task\scheduled_task {
    /** @var string Asset table. */
    private const ASSET_TABLE = 'local_fastpix_asset';
    /** @var int Only reconcile assets untouched for this long (30 min). */
    private const STUCK_AFTER_SECONDS = 1800;
    /** @var int Max assets polled per run. */
    private const BATCH_SIZE = 50;
    /** @var int Wall-clock budget per run. */
    private const TIME_BUDGET_SECONDS = 60;
    /** @var string[] Terminal statuses that need no reconciliation. */
    private const TERMINAL = ['ready', 'errored'];

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_reconcile_pending_assets', 'local_fastpix');
    }

    /**
     * Poll FastPix for stuck assets and project the authoritative status.
     */
    public function execute(): void {
        global $DB;

        $now = time();
        [$notinsql, $params] = $DB->get_in_or_equal(self::TERMINAL, SQL_PARAMS_NAMED, 'st', false);
        $params['cutoff'] = $now - self::STUCK_AFTER_SECONDS;

        $rows = $DB->get_records_select(
            self::ASSET_TABLE,
            "deleted_at IS NULL AND timemodified < :cutoff AND status {$notinsql}",
            $params,
            'timemodified ASC',
            'id, fastpix_id',
            0,
            self::BATCH_SIZE,
        );

        $start = microtime(true);
        $reconciled = 0;
        $processing = 0;
        $gateway   = \local_fastpix\api\gateway::instance();
        $projector = new \local_fastpix\webhook\projector();

        foreach ($rows as $row) {
            if ((microtime(true) - $start) > self::TIME_BUDGET_SECONDS) {
                mtrace('reconcile_pending_assets: time budget hit, deferring remainder.');
                break;
            }

            $fastpixid = (string)$row->fastpix_id;
            try {
                $remote = $gateway->get_media($fastpixid);
            } catch (gateway_not_found $e) {
                // Gone on FastPix → terminal failed.
                $this->project_failed($projector, $fastpixid, $now);
                $reconciled++;
                continue;
            } catch (gateway_unavailable $e) {
                // Breaker open / outage — stop polling and defer (ADR-016 guard).
                mtrace('reconcile_pending_assets: gateway unavailable, deferring run: ' . $e->getMessage());
                break;
            }

            $data   = $remote->data ?? $remote;
            $status = strtolower((string)($data->status ?? ''));

            if ($status === 'ready') {
                $this->project_ready($projector, $fastpixid, $data, $now);
                $reconciled++;
            } else if (in_array($status, ['failed', 'errored', 'cancelled'], true)) {
                $this->project_failed($projector, $fastpixid, $now);
                $reconciled++;
            } else {
                // Still processing on FastPix — try again next run.
                $processing++;
            }
        }

        mtrace("reconcile_pending_assets: reconciled {$reconciled}, still processing {$processing}");
    }

    /**
     * Project a synthetic ready event from authoritative FastPix data, through
     * the normal projector (locking + ordering + cache invalidation).
     *
     * @param \local_fastpix\webhook\projector $projector
     * @param string $fastpixid
     * @param \stdClass $data Media object from gateway::get_media.
     * @param int $now
     */
    private function project_ready(
        \local_fastpix\webhook\projector $projector,
        string $fastpixid,
        \stdClass $data,
        int $now
    ): void {
        $projector->project((object)[
            'id'         => 'reconcile-' . $fastpixid . '-' . $now,
            'type'       => 'video.media.ready',
            'occurredAt' => $now,
            'object'     => (object)['type' => 'media', 'id' => $fastpixid],
            'data'       => (object)[
                'status'      => 'Ready',
                'playbackIds' => $data->playbackIds ?? [],
                'duration'    => $data->duration ?? null,
                'metadata'    => $data->metadata ?? null,
            ],
        ]);
    }

    /**
     * Project a synthetic failed event (asset errored or gone on FastPix).
     *
     * @param \local_fastpix\webhook\projector $projector
     * @param string $fastpixid
     * @param int $now
     */
    private function project_failed(\local_fastpix\webhook\projector $projector, string $fastpixid, int $now): void {
        $projector->project((object)[
            'id'         => 'reconcile-fail-' . $fastpixid . '-' . $now,
            'type'       => 'video.media.failed',
            'occurredAt' => $now,
            'object'     => (object)['type' => 'media', 'id' => $fastpixid],
            'data'       => new \stdClass(),
        ]);
    }
}
