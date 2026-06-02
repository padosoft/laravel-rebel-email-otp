<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Otp;

use InvalidArgumentException;

/**
 * Genera un OTP numerico usando un generatore crittograficamente sicuro (CSPRNG).
 *
 *   (new NumericOtpGenerator)->generate(6); // es. "048213"
 *
 * Lo zero-padding è importante: "048213" deve restare a 6 cifre, non diventare "48213".
 */
final class NumericOtpGenerator
{
    public function generate(int $digits = 6): string
    {
        if ($digits < 4 || $digits > 10) {
            throw new InvalidArgumentException("Numero di cifre OTP non valido ({$digits}): ammessi 4-10.");
        }

        $max = (10 ** $digits) - 1;

        // random_int è CSPRNG (a differenza di rand()/mt_rand()).
        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }
}
