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
 * Scheduled or adhoc task: signing key rotator.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\task;

/**
 * Rotates the local RS256 signing key every 90 days. Keeps the previous key
 * around so JWTs already issued under it remain verifiable until natural TTL
 * expiry. NEVER logs the PEM (rule S1).
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signing_key_rotator extends \core\task\scheduled_task {
    /** @var int Rotate after seconds. */
    private const ROTATE_AFTER_SECONDS = 7776000; // 90 Days.

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_signing_key_rotator', 'local_fastpix');
    }

    /**
     * Web service main entry point.
     */    public function execute(): void {
        $createdat = (int)get_config('local_fastpix', 'signing_key_created_at');

        if ($createdat <= 0) {
            // First time we're tracking creation; record now and exit.
            set_config('signing_key_created_at', time(), 'local_fastpix');
            mtrace('signing_key_rotator: no creation timestamp; seeded with now');
            return;
        }

        if ((time() - $createdat) < self::ROTATE_AFTER_SECONDS) {
            return; // Not yet 90 days old.
        }

        $oldkid = (string)get_config('local_fastpix', 'signing_key_id');
        $oldpem = (string)get_config('local_fastpix', 'signing_private_key');

        try {
            // 1. Move the current key into the "previous" slot.
            set_config('signing_key_id_previous', $oldkid, 'local_fastpix');
            set_config('signing_private_key_previous', $oldpem, 'local_fastpix');
            set_config('signing_key_rotated_at', time(), 'local_fastpix');

            // 2. Mint a new key on the FastPix side.
            $response = \local_fastpix\api\gateway::instance()->create_signing_key();
            $newkid = (string)($response->id ?? '');
            $newpem = (string)($response->privateKey ?? '');

            if ($newkid === '' || $newpem === '') {
                throw new \RuntimeException('gateway returned empty signing key payload');
            }

            // 3. Store the new key (PEM base64-encoded for safe single-line storage).
            set_config('signing_key_id', $newkid, 'local_fastpix');
            set_config('signing_private_key', base64_encode($newpem), 'local_fastpix');
            set_config('signing_key_created_at', time(), 'local_fastpix');

            // Log only the kid — never the PEM (S1).
            mtrace("signing_key_rotator: rotated to new kid={$newkid}");
        } catch (\Throwable $e) {
            // Roll back the "previous" slot to whatever it was so we don't.
            // Leave a half-rotated state on the next run.
            set_config('signing_key_id_previous', '', 'local_fastpix');
            set_config('signing_private_key_previous', '', 'local_fastpix');
            set_config('signing_key_rotated_at', 0, 'local_fastpix');

            mtrace('signing_key_rotator: rotation failed; existing key retained: '
            . $e->getMessage());
        }
}
}
