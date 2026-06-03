<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Actions;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Results\StartEmailOtpResult;
use Psr\Clock\ClockInterface;

/**
 * Reinvia il codice: invalida la challenge precedente e ne crea una nuova,
 * rispettando un cooldown minimo e il numero massimo di reinvii (anti-abuso).
 *
 * Lo `status` del risultato segnala l'esito: 'ok' | 'cooldown' | 'max_resends'.
 */
final class ResendEmailOtpChallenge
{
    public function __construct(
        private readonly StartEmailOtpChallenge $start,
        private readonly KeyedHasher $keyedHasher,
        private readonly ClockInterface $clock,
        private readonly Repository $config,
    ) {}

    public function handle(EmailIdentifier $identifier, string $purpose, SecurityContext $context): StartEmailOtpResult
    {
        $identifierHashed = $this->keyedHasher->hash($identifier->normalized());
        $tenantId = $context->tenant?->id;

        $active = EmailOtpChallenge::query()
            ->where('identifier_hmac', $identifierHashed->hash)
            ->where('purpose', $purpose)
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereIn('status', [ChallengeStatus::Pending->value, ChallengeStatus::Sent->value])
            ->latest('created_at')
            ->first();

        if ($active !== null) {
            $updatedAt = $active->getAttribute('updated_at');
            $cooldown = $this->intConfig('rebel-email-otp.resend_cooldown_seconds', 30);

            if ($updatedAt instanceof DateTimeInterface
                && ($this->clock->now()->getTimestamp() - $updatedAt->getTimestamp()) < $cooldown) {
                return new StartEmailOtpResult(
                    $active->id,
                    $identifier->masked(),
                    'cooldown',
                    'Attendi qualche secondo prima di richiedere un nuovo codice.',
                );
            }

            if ($active->resends >= $this->intConfig('rebel-email-otp.max_resends', 3)) {
                return new StartEmailOtpResult(
                    $active->id,
                    $identifier->masked(),
                    'max_resends',
                    'Hai raggiunto il numero massimo di reinvii.',
                );
            }
        }

        $previousResends = $active !== null ? $active->resends : 0;

        $result = $this->start->handle($identifier, $purpose, $context);

        EmailOtpChallenge::query()
            ->whereKey($result->challengeId)
            ->update(['resends' => $previousResends + 1]);

        return $result;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_int($value) ? $value : $default;
    }
}
