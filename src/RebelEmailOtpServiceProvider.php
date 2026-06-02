<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp;

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
            ->hasConfigFile('rebel-email-otp');
    }
}
