<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Padosoft\Rebel\Core\RebelCoreServiceProvider;
use Padosoft\Rebel\EmailOtp\RebelEmailOtpServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            RebelCoreServiceProvider::class,
            RebelEmailOtpServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        // APP_KEY per il middleware web (cookie/sessione) nei test delle route.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        // Pepper di test per gli HMAC del core.
        $app['config']->set('rebel-core.peppers', [1 => 'test-pepper']);
        $app['config']->set('rebel-core.pepper_current', 1);
        // Niente sleep di timing-pad nei test (li renderebbe lentissimi).
        $app['config']->set('rebel-email-otp.timing_target_ms', 0);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Migrazioni di questo package + quelle del core (che vivono in vendor).
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/padosoft/laravel-rebel-core/database/migrations');
    }
}
