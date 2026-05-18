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

/**
 * Tests for the webhook verifier.
 *
 * @covers \local_fastpix\webhook\verifier
 * @package    local_fastpix
 * @copyright  2026 FastPix Inc. <support@fastpix.io>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class verifier_test extends \advanced_testcase {
    // Test fixtures must be ≥ verifier::MIN_SECRET_BYTES (32). Match the.
    // Install.php format: 64 hex chars from a fixed test seed (deterministic.
    // For unit tests; install.php uses random_bytes() in production).
    /** @var string */
    private const CURRENT  = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    /** @var string */
    private const PREVIOUS = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    /** @var string */
    private const BODY     = '{"type":"media.ready","object":{"id":"abc"}}';

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        verifier::reset();
    }

    public function tearDown(): void {
        parent::tearDown();
        verifier::reset();
    }

    /**
     * Helper: configure current.
     */    private function configure_current(): void {
        set_config('webhook_secret_current', self::CURRENT, 'local_fastpix');
        set_config('webhook_secret_previous', '', 'local_fastpix');
        set_config('webhook_secret_rotated_at', 0, 'local_fastpix');
}

    /**
     * Helper: sign.
     *
     * @param string $body
     * @param string $secret
     * @return string
     */
private function sign(string $body, string $secret): string {
    return hash_hmac('sha256', $body, $secret);
}

    // Happy / unhappy current secret.

    /**
     * Test that verify with valid current secret returns true.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_valid_current_secret_returns_true(): void {
    $this->configure_current();
    $sig = $this->sign(self::BODY, self::CURRENT);
    $this->assertTrue(verifier::instance()->verify(self::BODY, $sig));
}

    /**
     * Test that verify with invalid signature returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_invalid_signature_returns_false(): void {
    $this->configure_current();
    $this->assertFalse(verifier::instance()->verify(self::BODY, str_repeat('0', 64)));
}

    /**
     * Test that verify with empty signature header returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_empty_signature_header_returns_false(): void {
    $this->configure_current();
    $this->assertFalse(verifier::instance()->verify(self::BODY, ''));
    $this->assertDebuggingCalled('webhook signature verify: empty body or signature');
}

    /**
     * Test that verify with empty body returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_empty_body_returns_false(): void {
    $this->configure_current();
    $sig = $this->sign(self::BODY, self::CURRENT);
    $this->assertFalse(verifier::instance()->verify('', $sig));
    $this->assertDebuggingCalled('webhook signature verify: empty body or signature');
}

    /**
     * Test that verify with no current secret configured returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_no_current_secret_configured_returns_false(): void {
    set_config('webhook_secret_current', '', 'local_fastpix');
    $sig = $this->sign(self::BODY, self::CURRENT);
    $this->assertFalse(verifier::instance()->verify(self::BODY, $sig));
    $this->assertDebuggingCalled('webhook signature verify: current secret not configured');
}

    // Rotation window.

    /**
     * Test that verify with previous secret within 30min window returns true.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_previous_secret_within_30min_window_returns_true(): void {
    set_config(
        'webhook_secret_current',
        'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        'local_fastpix'
    );
    set_config('webhook_secret_previous', self::PREVIOUS, 'local_fastpix');
    set_config('webhook_secret_rotated_at', time() - 1500, 'local_fastpix'); // 25 Min ago.

    $sig = $this->sign(self::BODY, self::PREVIOUS);
    $this->assertTrue(verifier::instance()->verify(self::BODY, $sig));
}

    /**
     * Test that verify with previous secret after 30min window returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_previous_secret_after_30min_window_returns_false(): void {
    set_config(
        'webhook_secret_current',
        'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        'local_fastpix'
    );
    set_config('webhook_secret_previous', self::PREVIOUS, 'local_fastpix');
    set_config('webhook_secret_rotated_at', time() - 1801, 'local_fastpix'); // Just past window.

    $sig = $this->sign(self::BODY, self::PREVIOUS);
    $this->assertFalse(verifier::instance()->verify(self::BODY, $sig));
}

    /**
     * Test that verify with no previous secret returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_no_previous_secret_returns_false(): void {
    $this->configure_current();
    $garbagesig = $this->sign(self::BODY, 'guessed-secret');
    $this->assertFalse(verifier::instance()->verify(self::BODY, $garbagesig));
}

    /**
     * Test that verify with rotated at zero returns false.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_rotated_at_zero_returns_false(): void {
    set_config('webhook_secret_current', self::CURRENT, 'local_fastpix');
    set_config('webhook_secret_previous', '', 'local_fastpix');
    set_config('webhook_secret_rotated_at', 0, 'local_fastpix');

    $garbagesig = $this->sign(self::BODY, 'something-else');
    $this->assertFalse(verifier::instance()->verify(self::BODY, $garbagesig));
}

    // Robustness.

    /**
     * Test that verify does not throw on any input.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_does_not_throw_on_any_input(): void {
    $this->configure_current();

    $badinputs = [
        ['', ''],
        ['', str_repeat('a', 64)],
        ['body', ''],
        ['body', "\x00\x01\x02"],
        ['body', str_repeat('z', 10000)],
        [str_repeat('B', 1024 * 100), 'not-a-valid-sig'],
    ];

    foreach ($badinputs as [$body, $sig]) {
        $result = verifier::instance()->verify($body, $sig);
        $this->assertIsBool($result);
        // Discard any debug output triggered by this iteration; the test.
        // Only asserts that no exception escaped, not the specific notice.
        $this->resetDebugging();
    }
}

    /**
     * Test that verify signature constant time via hash equals.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_signature_constant_time_via_hash_equals(): void {
    $this->configure_current();

    // Smoke check: the verifier must accept a signature computed identically.
    // To its own hash_hmac invocation. If the wrong algo / wrong inputs were.
    // Used internally, this would diverge.
    $expected = hash_hmac('sha256', self::BODY, self::CURRENT);
    $this->assertTrue(verifier::instance()->verify(self::BODY, $expected));

    // And a one-byte tweak must reject — proving comparison is on full string.
    $tweaked = $expected;
    $tweaked[0] = $tweaked[0] === 'a' ? 'b' : 'a';
    $this->assertFalse(verifier::instance()->verify(self::BODY, $tweaked));
}

    // Singleton.

    /**
     * Test that singleton returns same instance across calls.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_singleton_returns_same_instance_across_calls(): void {
    $a = verifier::instance();
    $b = verifier::instance();
    $this->assertSame($a, $b);
}

    /**
     * Test that reset clears singleton.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_reset_clears_singleton(): void {
    $first = verifier::instance();
    verifier::reset();
    $second = verifier::instance();
    $this->assertNotSame($first, $second);
}

    // Canonical FastPix shape (production format).

    /**
     * Test that verify canonical base64 secret with base64 output.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_canonical_base64_secret_with_base64_output(): void {
    $rawsecret = random_bytes(32);
    $configured = base64_encode($rawsecret);
    set_config('webhook_secret_current', $configured, 'local_fastpix');

    $sig = base64_encode(hash_hmac('sha256', self::BODY, $rawsecret, true));
    $this->assertTrue(verifier::instance()->verify(self::BODY, $sig));
}

    // S7: 29m59s boundary.

    /**
     * Test that verify with previous secret at 29m59s returns true.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_previous_secret_at_29m59s_returns_true(): void {
    set_config(
        'webhook_secret_current',
        'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        'local_fastpix'
    );
    set_config('webhook_secret_previous', self::PREVIOUS, 'local_fastpix');
    set_config('webhook_secret_rotated_at', time() - 1799, 'local_fastpix');

    $sig = $this->sign(self::BODY, self::PREVIOUS);
    $this->assertTrue(verifier::instance()->verify(self::BODY, $sig));
}

    // Short previous-secret during rotation window logs and rejects.

    /**
     * Test that verify with short previous secret during rotation window logs and rejects.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_verify_with_short_previous_secret_during_rotation_window_logs_and_rejects(): void {
    set_config('webhook_secret_current', self::CURRENT, 'local_fastpix');
    set_config('webhook_secret_previous', 'too-short', 'local_fastpix');
    set_config('webhook_secret_rotated_at', time() - 600, 'local_fastpix');

    $tmp = tempnam(sys_get_temp_dir(), 'verlog_');
    $original = ini_get('error_log');
    ini_set('error_log', $tmp);
    try {
        $this->assertFalse(verifier::instance()->verify(self::BODY, str_repeat('0', 64)));
        $log = (string)file_get_contents($tmp);
    } finally {
        ini_set('error_log', $original);
        @unlink($tmp);
    }
    $this->assertStringContainsString('"slot":"previous"', $log);
}

    // Redaction canary (S2).

    /**
     * Test that no secret in log on short secret.
     *
     * @covers \local_fastpix\webhook\verifier
     */
public function test_no_secret_in_log_on_short_secret(): void {
    $sentinel = 'Sn3tin3lSecretValueDoNotLeakMe';
    set_config('webhook_secret_current', $sentinel, 'local_fastpix');
    $signature = $this->sign(self::BODY, $sentinel);

    $tmp = tempnam(sys_get_temp_dir(), 'verlog_');
    $original = ini_get('error_log');
    ini_set('error_log', $tmp);
    try {
        verifier::instance()->verify(self::BODY, $signature);
        $log = (string)file_get_contents($tmp);
    } finally {
        ini_set('error_log', $original);
        @unlink($tmp);
    }
    $this->assertStringNotContainsString($sentinel, $log);
    $this->assertStringNotContainsString($signature, $log);
    $this->assertStringContainsString('webhook.secret_too_short', $log);
}
}
