<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Padosoft\Rebel\Core\Concerns\BelongsToTenant;
use Padosoft\Rebel\EmailOtp\Enums\ChallengeStatus;

/**
 * Una challenge OTP via email.
 *
 * L'id è un ULID generato dall'azione di start (serve per comporre il code_hmac).
 * Il codice in chiaro NON è mai salvato: solo `code_hmac` (+ `code_salt`, `key_version`).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string|null $guard
 * @property string $purpose
 * @property string $identifier_type
 * @property string $identifier_hmac
 * @property int $key_version
 * @property string $code_salt
 * @property string|null $code_hmac
 * @property string|null $subject_type
 * @property int|string|null $subject_id
 * @property string|null $provider
 * @property string|null $provider_reference
 * @property string $channel
 * @property ChallengeStatus $status
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $consumed_at
 * @property int $attempts
 * @property int $resends
 * @property string|null $ip_hmac
 * @property string|null $user_agent_hash
 * @property string|null $idempotency_key
 * @property array<string, mixed>|null $risk_context
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class EmailOtpChallenge extends Model
{
    use BelongsToTenant;

    protected $table = 'rebel_email_otp_challenges';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'id', 'tenant_id', 'guard', 'purpose', 'identifier_type', 'identifier_hmac',
        'key_version', 'code_salt', 'code_hmac', 'subject_type', 'subject_id',
        'provider', 'provider_reference', 'channel', 'status', 'expires_at',
        'consumed_at', 'attempts', 'resends', 'ip_hmac', 'user_agent_hash',
        'idempotency_key', 'risk_context',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChallengeStatus::class,
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'risk_context' => 'array',
            'attempts' => 'integer',
            'resends' => 'integer',
            'key_version' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpiredAt(DateTimeInterface $now): bool
    {
        return $now > $this->expires_at;
    }

    public function isConsumed(): bool
    {
        return in_array($this->status, [ChallengeStatus::Consumed, ChallengeStatus::Verified], true);
    }
}
