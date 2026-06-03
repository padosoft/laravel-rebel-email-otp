<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

it('prunes challenges older than the given number of days', function (): void {
    Notification::fake();

    // One "old" challenge and one "recent" one.
    $old = app(RebelEmailOtp::class)->start(EmailIdentifier::from('old@example.it'), 'customer-login', new SecurityContext('x'));
    $recent = app(RebelEmailOtp::class)->start(EmailIdentifier::from('new@example.it'), 'customer-login', new SecurityContext('y'));

    EmailOtpChallenge::query()->whereKey($old->challengeId)->update(['created_at' => now()->subDays(30)]);

    $this->artisan('rebel:email-otp:prune', ['--days' => 7])->assertExitCode(0);

    expect(EmailOtpChallenge::query()->whereKey($old->challengeId)->exists())->toBeFalse()
        ->and(EmailOtpChallenge::query()->whereKey($recent->challengeId)->exists())->toBeTrue();
});
