<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Enums;

/**
 * Stati del ciclo di vita di una challenge OTP.
 *
 *  pending  → creata, non ancora inviata
 *  sent     → codice inviato, in attesa di verifica
 *  verified → codice corretto verificato
 *  consumed → usata (single-use): non più riutilizzabile
 *  failed   → verifica fallita
 *  expired  → scaduta (oltre il TTL) o invalidata da un reinvio
 *  blocked  → bloccata (troppi tentativi)
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
