<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

it('enforces the resend cooldown then allows a new code', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $identifier = EmailIdentifier::from('mario@example.it');
    $ctx = new SecurityContext('x');

    $first = $otp->start($identifier, 'customer-login', $ctx);

    // Right after start: too soon → cooldown.
    expect($otp->resend($identifier, 'customer-login', $ctx)->status)->toBe('cooldown');

    // Move created_at into the past to get past the cooldown (default 30s).
    EmailOtpChallenge::query()->whereKey($first->challengeId)->update(['created_at' => now()->subSeconds(120)]);

    $resent = $otp->resend($identifier, 'customer-login', $ctx);

    expect($resent->status)->toBe('ok')
        ->and($resent->challengeId)->not->toBe($first->challengeId)
        ->and(EmailOtpChallenge::query()->findOrFail($first->challengeId)->status)->toBe(ChallengeStatus::Expired)
        ->and(EmailOtpChallenge::query()->findOrFail($resent->challengeId)->resends)->toBe(1);
});

it('stops resending after the max number of resends', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $identifier = EmailIdentifier::from('mario@example.it');
    $ctx = new SecurityContext('x');

    $first = $otp->start($identifier, 'customer-login', $ctx);

    // Bring the active challenge to the resend limit (default 3) and out of cooldown.
    EmailOtpChallenge::query()->whereKey($first->challengeId)->update([
        'resends' => 3,
        'created_at' => now()->subSeconds(120),
    ]);

    expect($otp->resend($identifier, 'customer-login', $ctx)->status)->toBe('max_resends');
});
