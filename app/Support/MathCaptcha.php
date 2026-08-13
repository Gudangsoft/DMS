<?php

namespace App\Support;

/**
 * Simple addition-only CAPTCHA ("berapa hasil dari 4 + 7?") for the login
 * forms — enough friction to stop naive credential-stuffing bots without
 * needing a third-party service/API key. The expected answer lives only in
 * the session, keyed by a caller-supplied name so the public login and the
 * Filament admin login can each hold their own independent challenge.
 */
class MathCaptcha
{
    /**
     * @return array{a: int, b: int}
     */
    public static function generate(string $key = 'math_captcha'): array
    {
        $a = random_int(1, 10);
        $b = random_int(1, 10);

        session()->put("{$key}_answer", $a + $b);

        return ['a' => $a, 'b' => $b];
    }

    /**
     * One-time use — correct or not, the stored answer is consumed so the
     * same challenge can't be replayed.
     */
    public static function verify(string $key, mixed $answer): bool
    {
        $expected = session()->pull("{$key}_answer");

        if ($expected === null || $answer === null || $answer === '') {
            return false;
        }

        return is_numeric($answer) && (int) $answer === (int) $expected;
    }
}
