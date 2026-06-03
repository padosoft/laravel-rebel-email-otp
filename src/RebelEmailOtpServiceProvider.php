<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Contracts\SubjectResolver;
use Padosoft\Rebel\EmailOtp\Console\PruneChallengesCommand;
use Padosoft\Rebel\EmailOtp\Otp\OtpHasher;
use Padosoft\Rebel\EmailOtp\Resolvers\NullSubjectResolver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Passwordless login engine via email-OTP.
 *
 * Depends on padosoft/laravel-rebel-core for shared value objects/contracts
 * (identifiers, KeyedHasher, AuditLogger, LoginResult, Clock...).
 */
final class RebelEmailOtpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rebel-email-otp')
            ->hasConfigFile('rebel-email-otp')
            ->hasMigration('create_rebel_email_otp_challenges_table')
            ->hasViews('rebel-email-otp')
            ->hasAssets()
            ->hasCommand(PruneChallengesCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OtpHasher::class, function (Application $app): OtpHasher {
            return new OtpHasher($app->make(KeyedHasher::class));
        });

        // Default user resolver = none (the app provides its own).
        $this->app->bindIf(SubjectResolver::class, NullSubjectResolver::class);
    }

    public function packageBooted(): void
    {
        $config = $this->app->make(Repository::class);

        if ($config->get('rebel-email-otp.routes.enabled') !== true) {
            return;
        }

        $prefix = $config->get('rebel-email-otp.routes.prefix');
        $middleware = $config->get('rebel-email-otp.routes.middleware');

        Route::group([
            'prefix' => is_string($prefix) ? $prefix : 'account/login',
            'middleware' => is_array($middleware) ? $middleware : ['web'],
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
