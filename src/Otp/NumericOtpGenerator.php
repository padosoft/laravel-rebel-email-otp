<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Otp;

use InvalidArgumentException;

/**
 * Generates a numeric OTP using a cryptographically secure generator (CSPRNG).
 *
 *   (new NumericOtpGenerator)->generate(6); // e.g. "048213"
 *
 * Zero-padding matters: "048213" must stay 6 digits long, not become "48213".
 */
final class NumericOtpGenerator
{
    public function generate(int $digits = 6): string
    {
        if ($digits < 4 || $digits > 10) {
            throw new InvalidArgumentException("Invalid number of OTP digits ({$digits}): 4-10 are allowed.");
        }

        $max = (10 ** $digits) - 1;

        // random_int is a CSPRNG (unlike rand()/mt_rand()).
        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }
}
