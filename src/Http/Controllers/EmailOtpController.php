<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

/**
 * REFERENCE controller for the passwordless email-OTP login (web).
 *
 * It is deliberately simple: a real application can use its own controllers that
 * call the RebelEmailOtp facade. Here we show the complete
 * login → verify → done flow with session, publishable views and anti-enumeration.
 */
final class EmailOtpController extends Controller
{
    private const SESSION_KEY = 'rebel_otp';

    private const PURPOSE = 'customer-login';

    public function __construct(
        private readonly RebelEmailOtp $otp,
        private readonly KeyedHasher $hasher,
    ) {}

    public function showLogin(): Response
    {
        return response()->view('rebel-email-otp::login');
    }

    public function start(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $identifier = EmailIdentifier::from($request->string('email')->toString());
        $context = SecurityContext::fromRequest($request, $this->hasher)->withPurpose(self::PURPOSE);

        $result = $this->otp->start($identifier, self::PURPOSE, $context, $request->header('Idempotency-Key'));

        $request->session()->put(self::SESSION_KEY, [
            'email' => $identifier->normalized(),
            'challenge_id' => $result->challengeId,
            'masked' => $result->maskedIdentifier,
        ]);

        return redirect()->route('rebel-email-otp.verify-form');
    }

    public function showVerify(Request $request): Response|RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);

        if (! is_array($session)) {
            return redirect()->route('rebel-email-otp.login');
        }

        return response()->view('rebel-email-otp::verify', [
            'challengeId' => $session['challenge_id'] ?? '',
            'maskedEmail' => $session['masked'] ?? '',
            'email' => $session['email'] ?? '',
            'cooldown' => config('rebel-email-otp.resend_cooldown_seconds', 30),
            'digits' => config('rebel-email-otp.digits', 6),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $session = $request->session()->get(self::SESSION_KEY);
        $challengeId = $request->string('challenge_id')->toString();

        // The challenge_id must match the one opened in this session: this prevents
        // directly attacking someone else's challenge whose id is known.
        if (! is_array($session) || ($session['challenge_id'] ?? null) !== $challengeId) {
            return redirect()->route('rebel-email-otp.login');
        }

        $context = SecurityContext::fromRequest($request, $this->hasher)->withPurpose(self::PURPOSE);

        $result = $this->otp->verify(
            $challengeId,
            $request->string('code')->toString(),
            $context,
        );

        if (! $result->success) {
            return back()->withErrors(['code' => 'Codice non valido o scaduto.']);
        }

        $request->session()->forget(self::SESSION_KEY);

        // Demo: in a real app you would call Auth::login($result->subject) here (web)
        // or app(TokenIssuer)->issue($result->subject, $context) (mobile).
        return redirect()->route('rebel-email-otp.done');
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $identifier = EmailIdentifier::from($request->string('email')->toString());
        $context = SecurityContext::fromRequest($request, $this->hasher)->withPurpose(self::PURPOSE);

        $result = $this->otp->resend($identifier, self::PURPOSE, $context);

        $request->session()->put(self::SESSION_KEY, [
            'email' => $identifier->normalized(),
            'challenge_id' => $result->challengeId,
            'masked' => $result->maskedIdentifier,
        ]);

        return redirect()->route('rebel-email-otp.verify-form');
    }
}
