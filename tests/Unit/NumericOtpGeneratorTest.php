<?php

declare(strict_types=1);

use Padosoft\Rebel\EmailOtp\Otp\NumericOtpGenerator;

it('generates a zero-padded numeric code of the requested length', function (int $digits): void {
    $code = (new NumericOtpGenerator)->generate($digits);

    expect($code)->toHaveLength($digits)
        ->and($code)->toMatch('/^[0-9]+$/');
})->with([4, 6, 8, 10]);

it('keeps leading zeros (length is exact)', function (): void {
    // Su molte generazioni almeno una dovrebbe iniziare per 0 → verifichiamo che la
    // lunghezza resti sempre esatta (lo zero-padding non viene "mangiato").
    $generator = new NumericOtpGenerator;

    for ($i = 0; $i < 200; $i++) {
        expect($generator->generate(6))->toHaveLength(6);
    }
});

it('rejects an out-of-range number of digits', function (int $digits): void {
    (new NumericOtpGenerator)->generate($digits);
})->throws(InvalidArgumentException::class)->with([3, 11, 0]);
