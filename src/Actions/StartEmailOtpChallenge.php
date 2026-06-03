<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Actions;

use DateInterval;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Padosoft\Rebel\Core\Audit\AuditEvent;
use Padosoft\Rebel\Core\Audit\AuthEventType;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Contracts\SubjectResolver;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Padosoft\Rebel\EmailOtp\Notifications\EmailOtpNotification;
use Padosoft\Rebel\EmailOtp\Otp\NumericOtpGenerator;
use Padosoft\Rebel\EmailOtp\Otp\OtpHasher;
use Padosoft\Rebel\EmailOtp\Results\StartEmailOtpResult;
use Psr\Clock\ClockInterface;

/**
 * Start an email OTP challenge.
 *
 * Key security properties:
 *  - ANTI-ENUMERATION: it does not check whether the account exists before responding; it
 *    always creates a challenge, always sends the code, always responds generically,
 *    and NORMALISES the response time (timing pad) so nothing is revealed.
 *  - IDEMPOTENCY: with the same Idempotency-Key it does not resend (useful for mobile retries).
 *  - EXACTLY ONE active challenge per identifier+tenant+purpose (previous ones are expired).
 *  - The code is stored only as an HMAC (per-challenge salt + pepper).
 */
final class StartEmailOtpChallenge
{
    public function __construct(
        private readonly NumericOtpGenerator $generator,
        private readonly OtpHasher $otpHasher,
        private readonly KeyedHasher $keyedHasher,
        private readonly SubjectResolver $subjectResolver,
        private readonly AuditLogger $audit,
        private readonly ClockInterface $clock,
        private readonly Repository $config,
    ) {}

    public function handle(
        EmailIdentifier $identifier,
        string $purpose,
        SecurityContext $context,
        ?string $idempotencyKey = null,
    ): StartEmailOtpResult {
        $startedAt = $this->microtime();

        $identifierHashed = $this->keyedHasher->hash($identifier->normalized());
        $tenantId = $context->tenant?->id;

        // Idempotency: same key + identifier + purpose still active → do not resend.
        if ($idempotencyKey !== null) {
            $existing = EmailOtpChallenge::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('identifier_hmac', $identifierHashed->hash)
                ->where('purpose', $purpose)
                ->when(
                    $tenantId === null,
                    fn ($query) => $query->whereNull('tenant_id'),
                    fn ($query) => $query->where('tenant_id', $tenantId),
                )
                ->whereIn('status', [ChallengeStatus::Pending->value, ChallengeStatus::Sent->value])
                ->first();

            if ($existing !== null && ! $existing->isExpiredAt($this->clock->now())) {
                $this->padTiming($startedAt);

                return new StartEmailOtpResult($existing->id, $identifier->masked(), 'ok', $this->genericMessage());
            }
        }

        // Exactly one active challenge: invalidate the previous pending ones (tenant-safe scoping:
        // with a null tenant we do NOT touch other tenants' challenges).
        EmailOtpChallenge::query()
            ->where('identifier_hmac', $identifierHashed->hash)
            ->where('purpose', $purpose)
            ->when(
                $tenantId === null,
                fn ($query) => $query->whereNull('tenant_id'),
                fn ($query) => $query->where('tenant_id', $tenantId),
            )
            ->whereIn('status', [ChallengeStatus::Pending->value, ChallengeStatus::Sent->value])
            ->update(['status' => ChallengeStatus::Expired->value]);

        $id = (string) Str::ulid();
        $code = $this->generator->generate($this->intConfig('rebel-email-otp.digits', 6));
        $salt = bin2hex(random_bytes(16));
        $codeHashed = $this->otpHasher->hash($id, $code, $salt);
        $ttl = $this->intConfig('rebel-email-otp.ttl_seconds', 600);

        // "Internal" user resolution: it does not change the response (anti-enum), but if the
        // user exists we link them to the challenge so we know it at verify time.
        $subject = $this->subjectResolver->resolve($identifier, $context);

        $challenge = new EmailOtpChallenge;
        $challenge->forceFill([
            'id' => $id,
            'tenant_id' => $tenantId,
            'guard' => $context->guard,
            'purpose' => $purpose,
            'identifier_type' => $identifier->type(),
            'identifier_hmac' => $identifierHashed->hash,
            'key_version' => $codeHashed->keyVersion,
            'code_salt' => $salt,
            'code_hmac' => $codeHashed->hash,
            'channel' => 'email',
            'status' => ChallengeStatus::Sent,
            'expires_at' => $this->clock->now()->add(new DateInterval('PT'.$ttl.'S')),
            'ip_hmac' => $context->ipHmac,
            'user_agent_hash' => $context->userAgentHash,
            'idempotency_key' => $idempotencyKey,
            'risk_context' => $context->riskContext !== [] ? $context->riskContext : null,
        ]);

        if ($subject instanceof Model) {
            $challenge->subject()->associate($subject);
        }

        $challenge->save();

        Notification::route('mail', $identifier->normalized())
            ->notify(new EmailOtpNotification($code, $ttl, $purpose));

        $this->audit->record(new AuditEvent(
            type: AuthEventType::EmailOtpRequested->value,
            guard: $context->guard,
            identifierHmac: $identifierHashed->hash,
            keyVersion: $identifierHashed->keyVersion,
            ipHmac: $context->ipHmac,
            userAgentHash: $context->userAgentHash,
            tenantId: $tenantId,
            channel: 'email',
            purpose: $purpose,
        ));

        $this->padTiming($startedAt);

        return new StartEmailOtpResult($id, $identifier->masked(), 'ok', $this->genericMessage());
    }

    private function genericMessage(): string
    {
        return "Se l'email è corretta, ti abbiamo inviato un codice di accesso.";
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_int($value) ? $value : $default;
    }

    private function microtime(): float
    {
        return microtime(true);
    }

    /**
     * Normalise the response time to a target (+ jitter) so it does not reveal, through
     * timing, whether the account exists. Can be disabled with timing_target_ms <= 0 (useful in tests).
     *
     * Load note: it uses usleep() in the worker → under high concurrency it ties up the FPM pool.
     * Keep the target low (default 250ms) and protect `start` with a bot-gate/rate-limit upstream;
     * in extreme scenarios consider disabling it and normalising at the edge/proxy level.
     */
    private function padTiming(float $startedAt): void
    {
        $target = $this->intConfig('rebel-email-otp.timing_target_ms', 250);

        if ($target <= 0) {
            return;
        }

        $elapsedMs = ($this->microtime() - $startedAt) * 1000;
        $remainingMs = $target - $elapsedMs;

        if ($remainingMs > 0) {
            usleep((int) (($remainingMs + random_int(0, 50)) * 1000));
        }
    }
}
