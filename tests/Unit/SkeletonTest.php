<?php

declare(strict_types=1);

use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;

it('boots, loads its config and can see the core dependency', function (): void {
    expect(config('rebel-email-otp'))->toBeArray()
        ->and(config('rebel-email-otp.digits'))->toBe(6)
        // the core package must be installed and available
        ->and(class_exists(EmailIdentifier::class))->toBeTrue()
        ->and(EmailIdentifier::from('a@b.it')->type())->toBe('email');
});
