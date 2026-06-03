<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Results;

/**
 * Esito di "start": volutamente GENERICO (anti-enumeration). La risposta è identica
 * sia che l'account esista o no — quindi non rivela nulla. Contiene il challenge_id
 * necessario alla schermata di verifica e l'email mascherata da mostrare in UI.
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
