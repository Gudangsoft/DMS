<?php

namespace Tests\Unit\Support;

use App\Support\MathCaptcha;
use Tests\TestCase;

class MathCaptchaTest extends TestCase
{
    public function test_generate_returns_two_numbers_between_1_and_10(): void
    {
        $challenge = MathCaptcha::generate('test');

        $this->assertGreaterThanOrEqual(1, $challenge['a']);
        $this->assertLessThanOrEqual(10, $challenge['a']);
        $this->assertGreaterThanOrEqual(1, $challenge['b']);
        $this->assertLessThanOrEqual(10, $challenge['b']);
    }

    public function test_verify_succeeds_with_the_correct_sum(): void
    {
        $challenge = MathCaptcha::generate('test');

        $this->assertTrue(MathCaptcha::verify('test', $challenge['a'] + $challenge['b']));
    }

    public function test_verify_fails_with_the_wrong_answer(): void
    {
        $challenge = MathCaptcha::generate('test');

        $this->assertFalse(MathCaptcha::verify('test', $challenge['a'] + $challenge['b'] + 1));
    }

    public function test_verify_fails_when_no_challenge_was_ever_generated(): void
    {
        $this->assertFalse(MathCaptcha::verify('never_generated', 5));
    }

    public function test_verify_fails_on_blank_answer(): void
    {
        MathCaptcha::generate('test');

        $this->assertFalse(MathCaptcha::verify('test', null));
        $this->assertFalse(MathCaptcha::verify('test', ''));
    }

    public function test_verify_is_one_time_use(): void
    {
        $challenge = MathCaptcha::generate('test');
        $answer = $challenge['a'] + $challenge['b'];

        $this->assertTrue(MathCaptcha::verify('test', $answer));
        $this->assertFalse(MathCaptcha::verify('test', $answer));
    }

    public function test_different_keys_hold_independent_challenges(): void
    {
        $login = MathCaptcha::generate('login');
        MathCaptcha::generate('admin_login');

        // Verifying the "login" answer must not be affected by a different
        // challenge having been generated under the "admin_login" key.
        $this->assertTrue(MathCaptcha::verify('login', $login['a'] + $login['b']));
    }
}
