<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Enums;

/**
 * Lifecycle states of an OTP challenge.
 *
 *  pending  → created, not yet sent
 *  sent     → code sent, awaiting verification
 *  verified → correct code verified
 *  consumed → used (single-use): no longer reusable
 *  failed   → verification failed
 *  expired  → expired (past the TTL) or invalidated by a resend
 *  blocked  → blocked (too many attempts)
 */
enum ChallengeStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Verified = 'verified';
    case Consumed = 'consumed';
    case Failed = 'failed';
    case Expired = 'expired';
    case Blocked = 'blocked';
}
