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
 * Utility: cache keys.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fastpix\util;

/**
 * Single source of truth for the asset-cache key formula.
 * The MUC area 'local_fastpix/asset' is declared simplekeys=true, which
 * restricts keys to alphanumeric + underscore. The plugin caches each
 * asset row under TWO keys (the fastpix_id lookup and the playback_id
 * lookup), so we hash both ID strings and add a 2-char prefix to keep
 * the namespaces disjoint.
 * Hash: SHA-256 truncated to 32 hex chars (128 bits of effective output).
 * The truncation rationale and an empirical collision test live in
 * tests/cache_keys_collision_test.php. CRC32's 32-bit width gave a
 * ~77K-asset birthday-collision threshold which led to cross-asset
 * metadata leak — replaced per REVIEW §S-1.
 * Consumed by:
 *   - \local_fastpix\service\asset_service       (read + invalidate)
 *   - \local_fastpix\webhook\projector           (invalidate inside lock)
 *   - \local_fastpix\task\asset_cleanup          (invalidate after delete)
 *   - \local_fastpix\task\purge_soft_deleted_assets (invalidate on purge)
 * Any future caller that needs an asset-cache key MUST use these methods.
 *
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_keys {
    /**
     * Number of hex chars retained from the SHA-256 digest.
     */    private const TRUNCATE_TO = 32;

    /**
     * Fastpix.
     *
     * @param string $fastpixid
     * @return string
     */
    public static function fastpix(string $fastpixid): string {
        return 'fp_' . substr(hash('sha256', $fastpixid), 0, self::TRUNCATE_TO);
    }

    /**
     * Playback.
     *
     * @param string $playbackid
     * @return string
     */
    public static function playback(string $playbackid): string {
        return 'pb_' . substr(hash('sha256', $playbackid), 0, self::TRUNCATE_TO);
    }
}
