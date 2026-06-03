<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Padosoft\Rebel\Core\Clock\FakeClock;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Notifications\EmailOtpNotification;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;
use Psr\Clock\ClockInterface;

/**
 * Avvia una challenge e ritorna [challengeId, code] catturando il codice dalla notifica.
 *
 * @return array{0: string, 1: string}
 */
function startAndCapture(string $email = 'mario@example.it', string $purpose = 'customer-login'): array
{
    Notification::fake();

    $ctx = (new SecurityContext('req-1'))->withGuard('customers');
    $start = app(RebelEmailOtp::class)->start(EmailIdentifier::from($email), $purpose, $ctx);

    $code = '';
    Notification::assertSentOnDemand(EmailOtpNotification::class, function (EmailOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    return [$start->challengeId, $code];
}

it('completes the happy path: start → verify with the correct code', function (): void {
    [$challengeId, $code] = startAndCapture();

    $result = app(RebelEmailOtp::class)->verify($challengeId, $code, new SecurityContext('req-2'));

    expect($result->success)->toBeTrue()
        ->and($result->reason)->toBeNull()
        ->and(EmailOtpChallenge::query()->findOrFail($challengeId)->status)->toBe(ChallengeStatus::Consumed);
});

it('is single-use: a verified challenge cannot be replayed', function (): void {
    [$challengeId, $code] = startAndCapture();
    $otp = app(RebelEmailOtp::class);

    expect($otp->verify($challengeId, $code, new SecurityContext('a'))->success)->toBeTrue()
        ->and($otp->verify($challengeId, $code, new SecurityContext('b'))->reason)->toBe('already_used');
});

it('rejects a wrong code, counts attempts and blocks after the max', function (): void {
    [$challengeId] = startAndCapture();
    $otp = app(RebelEmailOtp::class);
    $ctx = new SecurityContext('x');

    // max_attempts default = 5: i primi 5 tentativi errati danno wrong_code...
    for ($i = 0; $i < 5; $i++) {
        expect($otp->verify($challengeId, '000000', $ctx)->reason)->toBe('wrong_code');
    }
    // ...poi la challenge è bloccata.
    expect($otp->verify($challengeId, '000000', $ctx)->reason)->toBe('blocked')
        ->and(EmailOtpChallenge::query()->findOrFail($challengeId)->status)->toBe(ChallengeStatus::Blocked);
});

it('rejects an unknown challenge id', function (): void {
    expect(app(RebelEmailOtp::class)->verify('non-existent', '123456', new SecurityContext('x'))->reason)
        ->toBe('invalid');
});

it('expires a challenge after its TTL (using a fake clock)', function (): void {
    $clock = new FakeClock(new DateTimeImmutable('2026-01-01 10:00:00'));
    app()->instance(ClockInterface::class, $clock);

    [$challengeId, $code] = startAndCapture();

    $clock->advance(601); // TTL default 600s

    expect(app(RebelEmailOtp::class)->verify($challengeId, $code, new SecurityContext('x'))->reason)
        ->toBe('expired');
});

it('responds generically regardless of the email (anti-enumeration)', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $ctx = new SecurityContext('x');

    $a = $otp->start(EmailIdentifier::from('exists@example.it'), 'customer-login', $ctx);
    $b = $otp->start(EmailIdentifier::from('nope@example.it'), 'customer-login', $ctx);

    expect($a->status)->toBe('ok')->and($b->status)->toBe('ok')
        ->and($a->message)->toBe($b->message)
        ->and($a->challengeId)->not->toBe($b->challengeId);
});

it('keeps only one active challenge per identifier+purpose', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $ctx = new SecurityContext('x');

    $first = $otp->start(EmailIdentifier::from('mario@example.it'), 'customer-login', $ctx);
    $otp->start(EmailIdentifier::from('mario@example.it'), 'customer-login', $ctx);

    expect(EmailOtpChallenge::query()->findOrFail($first->challengeId)->status)->toBe(ChallengeStatus::Expired);
});

it('is idempotent: same Idempotency-Key does not send a second code', function (): void {
    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $ctx = new SecurityContext('x');
    $id = EmailIdentifier::from('mario@example.it');

    $a = $otp->start($id, 'customer-login', $ctx, idempotencyKey: 'k-123');
    $b = $otp->start($id, 'customer-login', $ctx, idempotencyKey: 'k-123');

    expect($b->challengeId)->toBe($a->challengeId);
    Notification::assertCount(1);
});
