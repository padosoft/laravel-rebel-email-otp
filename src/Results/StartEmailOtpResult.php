<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Results;

/**
 * Outcome of "start": deliberately GENERIC (anti-enumeration). The response is identical
 * whether the account exists or not — so it reveals nothing. It contains the challenge_id
 * needed by the verification screen and the masked email to display in the UI.
 */
final readonly class StartEmailOtpResult
{
    public function __construct(
        public string $challengeId,
        public string $maskedIdentifier,
        public string $status = 'ok',
        public string $message = '',
    ) {}
}
