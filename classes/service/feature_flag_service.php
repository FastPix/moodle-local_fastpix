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
 * Service: feature flag service.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\service;

/**
 * Service: feature flag.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feature_flag_service {
    /** @var ?self $instance */
    private static ?self $instance = null;

    /**
     * Singleton accessor.
     *
     * @return self
     */
    public static function instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Drm enabled.
     *
     * @return bool
     */
    public function drm_enabled(): bool {
        // DOUBLE GATE: flag AND configuration_id (rule W12 / S-DRM).
        $flag = (bool)get_config('local_fastpix', 'feature_drm_enabled');
        $configid = (string)get_config('local_fastpix', 'drm_configuration_id');
        return $flag && $configid !== '';
    }

    /**
     * Watermark enabled.
     *
     * @return bool
     */
    public function watermark_enabled(): bool {
        return (bool)get_config('local_fastpix', 'feature_watermark_enabled');
    }

    /**
     * Tracking enabled.
     *
     * @return bool
     */
    public function tracking_enabled(): bool {
        return (bool)get_config('local_fastpix', 'feature_tracking_enabled');
    }

    /**
     * Drm configuration id.
     *
     * @return ?string
     */
    public function drm_configuration_id(): ?string {
        $id = (string)get_config('local_fastpix', 'drm_configuration_id');
        return $id !== '' ? $id : null;
    }

    /**
     * Snapshot.
     *
     * @return array
     */
    public function snapshot(): array {
        return [
            'drm'       => $this->drm_enabled(),
            'watermark' => $this->watermark_enabled(),
            'tracking'  => $this->tracking_enabled(),
        ];
    }

    /**
     * Reset the singleton (used by tests).
     */    public static function reset(): void {
        self::$instance = null;
}
}
