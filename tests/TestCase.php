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
        // APP_KEY for the web middleware (cookie/session) in the route tests.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        // Test pepper for the core HMACs.
        $app['config']->set('rebel-core.peppers', [1 => 'test-pepper']);
        $app['config']->set('rebel-core.pepper_current', 1);
        // No timing-pad sleep in tests (it would make them extremely slow).
        $app['config']->set('rebel-email-otp.timing_target_ms', 0);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Migrations of this package + those of the core (which live in vendor).
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/padosoft/laravel-rebel-core/database/migrations');
    }
}
