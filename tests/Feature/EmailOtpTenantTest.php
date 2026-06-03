<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Context\TenantContext;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

it('does not invalidate another tenant challenge when starting with a null tenant', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $email = EmailIdentifier::from('mario@example.it');

    // Active challenge for tenant "B".
    $b = $otp->start($email, 'customer-login', (new SecurityContext('x'))->withTenant(new TenantContext('B')));

    // Start with a null tenant for the SAME email: it must not touch tenant B's challenge.
    $otp->start($email, 'customer-login', new SecurityContext('y'));

    expect(EmailOtpChallenge::query()->findOrFail($b->challengeId)->status)->toBe(ChallengeStatus::Sent);
});

it('keeps idempotency scoped per tenant', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $email = EmailIdentifier::from('mario@example.it');

    $a = $otp->start($email, 'customer-login', (new SecurityContext('x'))->withTenant(new TenantContext('A')), idempotencyKey: 'k');
    $b = $otp->start($email, 'customer-login', (new SecurityContext('y'))->withTenant(new TenantContext('B')), idempotencyKey: 'k');

    // Same idempotency key but different tenants → different challenges.
    expect($b->challengeId)->not->toBe($a->challengeId);
});
