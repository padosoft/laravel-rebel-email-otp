<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Otp;

use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Hashing\HashedValue;

/**
 * Calcola/verifica l'HMAC di un OTP.
 *
 * Il valore hashato è la composizione `challengeId | code | salt`:
 *  - il `challengeId` (ULID) lega l'hash a quella specifica challenge;
 *  - il `salt` (random server-only) impedisce precomputazione e protegge anche se
 *    il pepper trapelasse;
 *  - il pepper (segreto, dentro KeyedHasher) è la chiave HMAC.
 *
 * Il confronto avviene a tempo costante (hash_equals dentro KeyedHasher).
 */
final class OtpHasher
{
    public function __construct(private readonly KeyedHasher $hasher) {}

    public function hash(string $challengeId, string $code, string $salt): HashedValue
    {
        return $this->hasher->hash($this->compose($challengeId, $code, $salt));
    }

    public function matches(string $challengeId, string $code, string $salt, string $hash, int $keyVersion): bool
    {
        return $this->hasher->matches($this->compose($challengeId, $code, $salt), $hash, $keyVersion);
    }

    private function compose(string $challengeId, string $code, string $salt): string
    {
        return $challengeId.'|'.$code.'|'.$salt;
    }
}
