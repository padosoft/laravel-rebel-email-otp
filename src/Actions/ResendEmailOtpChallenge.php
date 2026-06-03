<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Actions;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Results\StartEmailOtpResult;
use Psr\Clock\ClockInterface;

/**
 * Resend the code: invalidate the previous challenge and create a new one,
 * honouring a minimum cooldown and the maximum number of resends (anti-abuse).
 *
 * Everything runs inside a TRANSACTION with lockForUpdate on the active challenge, so
 * two concurrent resends cannot both exceed the limit (race on max_resends).
 *
 * The cooldown is based on `created_at` (= the time the code was sent), NOT on `updated_at`:
 * otherwise failed verify attempts (which touch updated_at) would reset its timer.
 *
 * The result `status` reports the outcome: 'ok' | 'cooldown' | 'max_resends'.
 */
final class ResendEmailOtpChallenge
{
    public function __construct(
        private readonly StartEmailOtpChallenge $start,
        private readonly KeyedHasher $keyedHasher,
        private readonly ClockInterface $clock,
        private readonly Repository $config,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(EmailIdentifier $identifier, string $purpose, SecurityContext $context): StartEmailOtpResult
    {
        $identifierHashed = $this->keyedHasher->hash($identifier->normalized());
        $tenantId = $context->tenant?->id;
        $cooldown = $this->intConfig('rebel-email-otp.resend_cooldown_seconds', 30);
        $maxResends = $this->intConfig('rebel-email-otp.max_resends', 3);

        return $this->db->connection()->transaction(function () use ($identifier, $purpose, $context, $identifierHashed, $tenantId, $cooldown, $maxResends): StartEmailOtpResult {
            $active = EmailOtpChallenge::query()
                ->where('identifier_hmac', $identifierHashed->hash)
                ->where('purpose', $purpose)
                ->when(
                    $tenantId === null,
                    fn ($query) => $query->whereNull('tenant_id'),
                    fn ($query) => $query->where('tenant_id', $tenantId),
                )
                ->whereIn('status', [ChallengeStatus::Pending->value, ChallengeStatus::Sent->value])
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if ($active !== null) {
                $createdAt = $active->getAttribute('created_at');

                if ($createdAt instanceof DateTimeInterface
                    && ($this->clock->now()->getTimestamp() - $createdAt->getTimestamp()) < $cooldown) {
                    return new StartEmailOtpResult(
                        $active->id,
                        $identifier->masked(),
                        'cooldown',
                        'Attendi qualche secondo prima di richiedere un nuovo codice.',
                    );
                }

                if ($active->resends >= $maxResends) {
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
        });
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_int($value) ? $value : $default;
    }
}
