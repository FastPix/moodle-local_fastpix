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

namespace local_fastpix\service;

/**
 * Tests for the playback service.
 *
 * @covers \local_fastpix\service\playback_service
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class playback_service_test extends \advanced_testcase {
    /** @var string */
    private const TABLE = 'local_fastpix_asset';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('local_fastpix', 'asset')->purge();
    }

    /**
     * Helper: bootstrap signing key.
     */    private function bootstrap_signing_key(): void {
        // The fastest path: generate an RSA keypair in-process, store via.
        // Config so jwt_signing_service can mint without a gateway call.
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $pem);
        set_config('signing_key_id', 'kid-test', 'local_fastpix');
        set_config('signing_private_key', base64_encode($pem), 'local_fastpix');
}

    /**
     * Helper: insert asset.
     *
     * @param array $overrides
     * @return \stdClass
     */
private function insert_asset(array $overrides = []): \stdClass {
    global $DB;
    $now = time();
    $row = (object)array_merge([
        'fastpix_id'             => 'media-' . random_string(8),
        'playback_id'            => 'pb-' . random_string(8),
        'owner_userid'           => 0,
        'title'                  => 'Test',
        'duration'               => 10.0,
        'status'                 => 'ready',
        'access_policy'          => 'public',
        'drm_required'           => 0,
        'no_skip_required'       => 0,
        'has_captions'           => 0,
        'last_event_id'          => null,
        'last_event_at'          => null,
        'deleted_at'             => null,
        'gdpr_delete_pending_at' => null,
        'gdpr_delete_attempts'   => 0,
        'timecreated'            => $now,
        'timemodified'           => $now,
    ], $overrides);
    $row->id = $DB->insert_record(self::TABLE, $row);
    return $row;
}

    /**
     * Test that resolve returns payload for ready asset.
     *
     * @covers \local_fastpix\service\playback_service
     */
public function test_resolve_returns_payload_for_ready_asset(): void {
    $this->bootstrap_signing_key();
    $asset = $this->insert_asset();

    $payload = playback_service::resolve($asset->fastpix_id, 42);

    $this->assertSame($asset->playback_id, $payload->playbackid);
    $this->assertNotEmpty($payload->playbacktoken);
    $this->assertGreaterThan(time(), $payload->expiresatts);
    $this->assertFalse($payload->drmrequired);
}

    /**
     * Test that resolve throws asset not found for missing.
     *
     * @covers \local_fastpix\service\playback_service
     */
public function test_resolve_throws_asset_not_found_for_missing(): void {
    $this->expectException(\local_fastpix\exception\asset_not_found::class);
    playback_service::resolve('media-nonexistent', 42);
}

    /**
     * Test that resolve throws asset not found for soft deleted.
     *
     * @covers \local_fastpix\service\playback_service
     */
public function test_resolve_throws_asset_not_found_for_soft_deleted(): void {
    $this->bootstrap_signing_key();
    $asset = $this->insert_asset(['deleted_at' => time() - 60]);
    $this->expectException(\local_fastpix\exception\asset_not_found::class);
    playback_service::resolve($asset->fastpix_id, 42);
}

    /**
     * Test that resolve throws asset not ready when status not ready.
     *
     * @covers \local_fastpix\service\playback_service
     */
public function test_resolve_throws_asset_not_ready_when_status_not_ready(): void {
    $this->bootstrap_signing_key();
    $asset = $this->insert_asset(['status' => 'created']);
    $this->expectException(\local_fastpix\exception\asset_not_ready::class);
    playback_service::resolve($asset->fastpix_id, 42);
}

    /**
     * Test that resolve sets drm required when asset is drm.
     *
     * @covers \local_fastpix\service\playback_service
     */
public function test_resolve_sets_drm_required_when_asset_is_drm(): void {
    $this->bootstrap_signing_key();
    $asset = $this->insert_asset(['drm_required' => 1, 'access_policy' => 'drm']);
    $payload = playback_service::resolve($asset->fastpix_id, 42);
    $this->assertTrue($payload->drmrequired);
}
}
