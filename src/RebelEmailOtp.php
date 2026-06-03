<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp;

use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Actions\ResendEmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Actions\StartEmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Actions\VerifyEmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Results\StartEmailOtpResult;
use Padosoft\Rebel\EmailOtp\Results\VerifyEmailOtpResult;

/**
 * Punto d'ingresso del package email-OTP. Risolvibile dal container:
 *
 *   $otp = app(RebelEmailOtp::class);
 *   $start  = $otp->start(EmailIdentifier::from($email), 'customer-login', $ctx);
 *   $result = $otp->verify($start->challengeId, $code, $ctx);
 */
final class RebelEmailOtp
{
    public function __construct(
        private readonly StartEmailOtpChallenge $startAction,
        private readonly VerifyEmailOtpChallenge $verifyAction,
        private readonly ResendEmailOtpChallenge $resendAction,
    ) {}

    public function start(
        EmailIdentifier $identifier,
        string $purpose,
        SecurityContext $context,
        ?string $idempotencyKey = null,
    ): StartEmailOtpResult {
        return $this->startAction->handle($identifier, $purpose, $context, $idempotencyKey);
    }

    public function verify(string $challengeId, string $code, SecurityContext $context): VerifyEmailOtpResult
    {
        return $this->verifyAction->handle($challengeId, $code, $context);
    }

    public function resend(EmailIdentifier $identifier, string $purpose, SecurityContext $context): StartEmailOtpResult
    {
        return $this->resendAction->handle($identifier, $purpose, $context);
    }
}
