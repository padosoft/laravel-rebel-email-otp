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
        // Pepper di test per gli HMAC del core.
        $app['config']->set('rebel-core.peppers', [1 => 'test-pepper']);
        $app['config']->set('rebel-core.pepper_current', 1);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
