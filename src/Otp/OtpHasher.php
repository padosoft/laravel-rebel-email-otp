<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Otp;

use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Hashing\HashedValue;

/**
 * Computes/verifies the HMAC of an OTP.
 *
 * The hashed value is the composition `challengeId | code | salt`:
 *  - the `challengeId` (ULID) binds the hash to that specific challenge;
 *  - the `salt` (server-only random) prevents precomputation and protects even if
 *    the pepper were leaked;
 *  - the pepper (a secret, inside KeyedHasher) is the HMAC key.
 *
 * The comparison is performed in constant time (hash_equals inside KeyedHasher).
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
