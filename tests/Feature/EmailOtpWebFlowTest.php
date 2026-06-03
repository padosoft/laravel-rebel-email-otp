<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Padosoft\Rebel\EmailOtp\Notifications\EmailOtpNotification;

function captureOtpCode(): string
{
    $code = '';
    Notification::assertSentOnDemand(EmailOtpNotification::class, function (EmailOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    return $code;
}

it('renders the login page', function (): void {
    $this->get(route('rebel-email-otp.login'))
        ->assertOk()
        ->assertSee('Accedi')
        ->assertSee('data-rebel-otp-start', false);
});

it('starts a challenge and redirects to the verify page showing the masked email', function (): void {
    Notification::fake();

    $this->post(route('rebel-email-otp.start'), ['email' => 'mario@example.it'])
        ->assertRedirect(route('rebel-email-otp.verify-form'));

    $this->get(route('rebel-email-otp.verify-form'))
        ->assertOk()
        ->assertSee('m***@example.it')
        ->assertSee('data-rebel-otp-input', false);
});

it('completes the web login with the correct code', function (): void {
    Notification::fake();

    $this->post(route('rebel-email-otp.start'), ['email' => 'mario@example.it'])->assertRedirect();
    $code = captureOtpCode();
    $challengeId = session('rebel_otp')['challenge_id'];

    $this->post(route('rebel-email-otp.verify'), ['challenge_id' => $challengeId, 'code' => $code])
        ->assertRedirect(route('rebel-email-otp.done'));
});

it('shows a validation error on a wrong code', function (): void {
    Notification::fake();

    $this->post(route('rebel-email-otp.start'), ['email' => 'mario@example.it'])->assertRedirect();
    $challengeId = session('rebel_otp')['challenge_id'];

    $this->from(route('rebel-email-otp.verify-form'))
        ->post(route('rebel-email-otp.verify'), ['challenge_id' => $challengeId, 'code' => '000000'])
        ->assertRedirect(route('rebel-email-otp.verify-form'))
        ->assertSessionHasErrors('code');
});

it('validates the email on start', function (): void {
    $this->from(route('rebel-email-otp.login'))
        ->post(route('rebel-email-otp.start'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

it('redirects to login when opening verify without an active session', function (): void {
    $this->get(route('rebel-email-otp.verify-form'))
        ->assertRedirect(route('rebel-email-otp.login'));
});

it('rejects a verify whose challenge_id does not match the session', function (): void {
    Notification::fake();
    $this->post(route('rebel-email-otp.start'), ['email' => 'mario@example.it'])->assertRedirect();

    // An arbitrary challenge_id different from the one in session → back to login (no direct brute force).
    $this->post(route('rebel-email-otp.verify'), ['challenge_id' => 'someone-else-id', 'code' => '123456'])
        ->assertRedirect(route('rebel-email-otp.login'));
});
