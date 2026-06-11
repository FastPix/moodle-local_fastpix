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
 * Admin setting: setting webhook secret.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\admin;

/**
 * Webhook-secret admin setting that performs rotation as a save side-effect.
 * FastPix generates the signing secret on its side and the admin pastes it
 * into Moodle. Pasting a NEW value (different from the existing
 * webhook_secret_current) is treated as a rotation:
 *   1. Validate length: empty allowed (admin disabling); >= 32 chars
 *      otherwise. Reject with a clear error message on too-short input.
 *   2. Persist the new value via the parent
 *      \admin_setting_configpasswordunmask::write_setting().
 *   3. AFTER the save succeeds AND the value actually changed AND a
 *      previous value existed, perform the rotation side-effects:
 *        - webhook_secret_previous = old current value
 *        - webhook_secret_rotated_at = time()
 *        - fire \local_fastpix\event\webhook_secret_rotated for audit
 *   The verifier honors the previous secret for ROTATION_WINDOW (1800s),
 *   so admins have a 30-minute overlap window for FastPix-side
 *   propagation if they're updating the secret in both places near
 *   simultaneously.
 * Errors during the audit-event trigger MUST NOT roll back the save -
 * that would leave the admin with a confusing partial state. The catch
 * swallows audit failures; ops will see the missing event but the
 * persisted state is correct.
 * Extends \admin_setting_configtext (NOT \admin_setting_configpasswordunmask)
 * because the passwordunmask widget depends on a JS-driven affordance that
 * is unreliable in some Moodle / theme combinations. The webhook secret is
 * stored as plaintext in mdl_config_plugins regardless of the widget (rule
 * S8 - documented in README.md), so the visual mask was cosmetic.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Class name is snake_case by Moodle Frankenstyle (rule M2) — the autoloader maps
// \local_fastpix\admin\setting_webhook_secret to this file. SonarQube php:S101
// (PascalCase) is a documented false positive; see docs/review/SONARQUBE-2026-06-11.md §4.
class setting_webhook_secret extends \admin_setting_configtext { // NOSONAR
    /**
     * Minimum acceptable length, mirroring verifier::MIN_SECRET_BYTES.
     */    private const MIN_LEN = 32;

    /**
     * Write setting.
     *
     * @param mixed $data
     */
    public function write_setting($data) {
        $newvalue = is_string($data) ? trim($data) : '';

        if ($newvalue !== '' && strlen($newvalue) < self::MIN_LEN) {
            return get_string('setting_webhook_secret_too_short', 'local_fastpix');
        }

        $oldvalue = (string)get_config($this->plugin, $this->name);

        $error = parent::write_setting($newvalue);
        if ($error !== '') {
            return $error;
        }

        if ($newvalue !== $oldvalue && $oldvalue !== '') {
            $now = time();
            set_config('webhook_secret_previous', $oldvalue, 'local_fastpix');
            set_config('webhook_secret_rotated_at', $now, 'local_fastpix');

            try {
                \local_fastpix\event\webhook_secret_rotated::create([
                    'context' => \context_system::instance(),
                    'other'   => ['rotated_at' => $now],
                ])->trigger();
            } catch (\Throwable $e) {
                debugging('local_fastpix: webhook_secret_rotated event failed: '
                    . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return '';
    }
}
