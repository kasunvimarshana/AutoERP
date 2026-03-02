<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Services\AuthService;

/**
 * Auth module service provider.
 *
 * Registers JWT guard configuration, RBAC services,
 * loads migrations, and registers routes.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register bindings.
     */
    public function register(): void
    {
        $this->app->bind(AuthService::class, AuthService::class);

        $this->mergeConfigFrom(
            __DIR__.'/../../config/auth.php',
            'auth_module'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );

        $this->publishes([
            __DIR__.'/../../config/auth.php' => config_path('auth_module.php'),
        ], 'auth-config');
    }
}
