<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\SubjectResolver;
use Padosoft\Rebel\Core\Identifiers\AuthIdentifier;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\Notifications\EmailOtpNotification;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

class OtpTestUser extends User
{
    protected $table = 'otp_test_users';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('otp_test_users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });
});

it('resolves and links the subject so verify returns the user', function (): void {
    $user = OtpTestUser::query()->create(['email' => 'mario@example.it']);

    // Fake application resolver: email → user.
    app()->instance(SubjectResolver::class, new class($user) implements SubjectResolver
    {
        public function __construct(private readonly OtpTestUser $user) {}

        public function resolve(AuthIdentifier $identifier, SecurityContext $context): ?Authenticatable
        {
            return $this->user;
        }
    });

    Notification::fake();
    $otp = app(RebelEmailOtp::class);
    $start = $otp->start(EmailIdentifier::from('mario@example.it'), 'customer-login', new SecurityContext('x'));

    $code = '';
    Notification::assertSentOnDemand(EmailOtpNotification::class, function (EmailOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    $result = $otp->verify($start->challengeId, $code, new SecurityContext('y'));

    expect($result->success)->toBeTrue()
        ->and($result->subject)->not->toBeNull()
        ->and($result->subject?->getAuthIdentifier())->toBe($user->getKey());
});
