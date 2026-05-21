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
 * Admin setting: FastPix API credential (apikey / apisecret).
 *
 * Marks a rotation flag on change so the signing key is re-bootstrapped
 * against the new workspace on next playback. Without this, an admin
 * changing credentials leaves the existing signing_key_id pointing at
 * the old FastPix workspace — every subsequent JWT mint produces a
 * token the new workspace's CDN rejects with HTTP 401.
 *
 * The actual rotation runs lazily in credential_service::ensure_signing_key,
 * triggered by the next playback request. Deferring keeps the admin form
 * save fast (no synchronous gateway call) and avoids the race where
 * apikey writes before apisecret in the same form submission.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\admin;

class setting_credential extends \admin_setting_configtext {
    /** @var bool Module-scoped guard: rotation shutdown fn registered once per request. */
    private static bool $rotationscheduled = false;

    /**
     * Write the setting; mark signing-key rotation required on change AND
     * schedule a synchronous rotation at end-of-request.
     *
     * The shutdown function runs AFTER all admin settings have been
     * persisted, so by the time it fires both apikey and apisecret are
     * coherent in the DB. If the request happens to end abnormally or
     * the rotation throws (e.g. FastPix unreachable), the flag stays set
     * and ensure_signing_key() lazily re-tries on the next playback.
     *
     * @param mixed $data
     * @return string Empty on success, error message on failure.
     */
    public function write_setting($data) {
        $newvalue = is_string($data) ? trim($data) : '';
        $oldvalue = (string)get_config($this->plugin, $this->name);

        $error = parent::write_setting($newvalue);
        if ($error !== '') {
            return $error;
        }

        if ($newvalue !== $oldvalue) {
            set_config('signing_key_rotation_required', '1', 'local_fastpix');
            $this->schedule_synchronous_rotation();
        }
        return '';
    }

    /**
     * Register a shutdown function (once per request) that calls
     * ensure_signing_key(). The flag set above forces ensure_signing_key()
     * down its "rotate path" — wipes stale key state, mints fresh under
     * the new credentials, registers the public key with FastPix.
     */
    private function schedule_synchronous_rotation(): void {
        if (self::$rotationscheduled) {
            return;
        }
        self::$rotationscheduled = true;

        \core_shutdown_manager::register_function(static function (): void {
            try {
                \local_fastpix\service\credential_service::instance()->ensure_signing_key();
            } catch (\Throwable $e) {
                // Leave the rotation_required flag set so a subsequent
                // playback request retries lazily. NEVER log the PEM (S1).
                debugging(
                    'local_fastpix: synchronous signing-key rotation failed; '
                    . 'flag remains set for lazy retry: ' . $e->getMessage(),
                    DEBUG_DEVELOPER,
                );
            }
        });
    }
}
