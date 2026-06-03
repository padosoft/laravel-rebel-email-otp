<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Results;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Outcome of "verify".
 *
 *  - success: correct code and challenge consumed (single-use);
 *  - reason:  failure reason (machine-readable), null on success;
 *  - subject: the resolved user (if it exists). It can be null even on success:
 *    in B2C "account-on-demand" scenarios the caller decides whether to create it.
 *
 * The caller then turns the subject into a login:
 *   web    → Auth::login($subject)
 *   mobile → app(TokenIssuer)->issue($subject, $ctx)  → TokenPair
 */
final readonly class VerifyEmailOtpResult
{
    private function __construct(
        public bool $success,
        public ?string $reason = null,
        public ?Authenticatable $subject = null,
    ) {}

    public static function success(?Authenticatable $subject): self
    {
        return new self(true, null, $subject);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
