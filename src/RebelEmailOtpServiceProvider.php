<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp;

use Illuminate\Contracts\Foundation\Application;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Contracts\SubjectResolver;
use Padosoft\Rebel\EmailOtp\Otp\OtpHasher;
use Padosoft\Rebel\EmailOtp\Resolvers\NullSubjectResolver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Engine di login passwordless via email-OTP.
 *
 * Dipende da padosoft/laravel-rebel-core per value object/contratti condivisi
 * (identificatori, KeyedHasher, AuditLogger, LoginResult, Clock...).
 */
final class RebelEmailOtpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rebel-email-otp')
            ->hasConfigFile('rebel-email-otp')
            ->hasMigration('create_rebel_email_otp_challenges_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OtpHasher::class, function (Application $app): OtpHasher {
            return new OtpHasher($app->make(KeyedHasher::class));
        });

        // Resolver utente di default = nessuno (l'app fornisce il suo).
        $this->app->bindIf(SubjectResolver::class, NullSubjectResolver::class);
    }
}
