<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Results;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Esito di "verify".
 *
 *  - success: codice corretto e challenge consumata (single-use);
 *  - reason:  motivo del fallimento (machine-readable), null se successo;
 *  - subject: l'utente risolto (se esiste). Può essere null anche in caso di successo:
 *    in scenari B2C "account-on-demand" il chiamante decide se crearlo.
 *
 * Il chiamante traduce poi il subject in login:
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
