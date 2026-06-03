<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Padosoft\Rebel\Core\Assurance\Aal;
use Padosoft\Rebel\Core\Audit\AuditEvent;
use Padosoft\Rebel\Core\Audit\AuthEventType;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Otp\OtpHasher;
use Padosoft\Rebel\EmailOtp\Results\VerifyEmailOtpResult;
use Psr\Clock\ClockInterface;

/**
 * Verifica un OTP in modo ATOMICO e single-use.
 *
 *  - lock pessimistico sulla riga (lockForUpdate in transazione): due verify
 *    concorrenti non possono entrambe passare;
 *  - replay: una challenge già consumata/verificata non è riutilizzabile;
 *  - scadenza e max tentativi gestiti (blocco dopo troppi errori);
 *  - confronto del codice a tempo costante (hash_equals dentro OtpHasher);
 *  - ritorna l'eventuale subject (utente) legato alla challenge.
 */
final class VerifyEmailOtpChallenge
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly OtpHasher $otpHasher,
        private readonly AuditLogger $audit,
        private readonly ClockInterface $clock,
        private readonly Repository $config,
    ) {}

    public function handle(string $challengeId, string $code, SecurityContext $context): VerifyEmailOtpResult
    {
        $maxAttempts = $this->intConfig('rebel-email-otp.max_attempts', 5);
        $now = CarbonImmutable::instance($this->clock->now());
        $tenantId = $context->tenant?->id;

        return $this->db->connection()->transaction(function () use ($challengeId, $code, $maxAttempts, $now, $tenantId): VerifyEmailOtpResult {
            // Isolamento tenant: una challenge si verifica SOLO nello stesso contesto-tenant
            // in cui è stata avviata (con tenant null deve avere tenant_id null).
            $challenge = EmailOtpChallenge::query()
                ->whereKey($challengeId)
                ->when(
                    $tenantId === null,
                    fn ($query) => $query->whereNull('tenant_id'),
                    fn ($query) => $query->where('tenant_id', $tenantId),
                )
                ->lockForUpdate()
                ->first();

            if ($challenge === null) {
                $this->auditFailure(null, 'invalid');

                return VerifyEmailOtpResult::failure('invalid');
            }

            if ($challenge->isConsumed()) {
                $this->auditFailure($challenge, 'already_used');

                return VerifyEmailOtpResult::failure('already_used');
            }

            if ($challenge->status === ChallengeStatus::Blocked) {
                $this->auditFailure($challenge, 'blocked');

                return VerifyEmailOtpResult::failure('blocked');
            }

            if ($challenge->isExpiredAt($now)) {
                $challenge->status = ChallengeStatus::Expired;
                $challenge->save();
                $this->auditFailure($challenge, 'expired');

                return VerifyEmailOtpResult::failure('expired');
            }

            if ($challenge->attempts >= $maxAttempts) {
                $challenge->status = ChallengeStatus::Blocked;
                $challenge->save();
                $this->auditFailure($challenge, 'too_many_attempts');

                return VerifyEmailOtpResult::failure('too_many_attempts');
            }

            $challenge->attempts++;

            $codeOk = $challenge->code_hmac !== null
                && $this->otpHasher->matches($challengeId, $code, $challenge->code_salt, $challenge->code_hmac, $challenge->key_version);

            if (! $codeOk) {
                $challenge->status = $challenge->attempts >= $maxAttempts
                    ? ChallengeStatus::Blocked
                    : ChallengeStatus::Sent;
                $challenge->save();
                $this->auditFailure($challenge, 'wrong_code');

                return VerifyEmailOtpResult::failure('wrong_code');
            }

            $challenge->status = ChallengeStatus::Consumed;
            $challenge->consumed_at = $now;
            $challenge->save();

            $this->audit->record(new AuditEvent(
                type: AuthEventType::EmailOtpVerified->value,
                guard: $challenge->guard,
                identifierHmac: $challenge->identifier_hmac,
                keyVersion: $challenge->key_version,
                tenantId: $challenge->tenant_id,
                channel: 'email',
                purpose: $challenge->purpose,
                aal: Aal::Aal1,
                amr: ['otp', 'email'],
            ));

            $subject = $challenge->subject;

            return VerifyEmailOtpResult::success($subject instanceof Authenticatable ? $subject : null);
        });
    }

    private function auditFailure(?EmailOtpChallenge $challenge, string $reason): void
    {
        $this->audit->record(new AuditEvent(
            type: AuthEventType::EmailOtpFailed->value,
            guard: $challenge?->guard,
            identifierHmac: $challenge?->identifier_hmac,
            keyVersion: $challenge?->key_version,
            tenantId: $challenge?->tenant_id,
            channel: 'email',
            purpose: $challenge?->purpose,
            metadata: ['reason' => $reason],
        ));
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_int($value) ? $value : $default;
    }
}
