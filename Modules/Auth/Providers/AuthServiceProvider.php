<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Auth\Services\JwtTokenService;

/**
 * AuthServiceProvider
 *
 * Bootstraps the authentication module
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register JWT token service
        $this->app->singleton(TokenServiceInterface::class, function ($app) {
            return new JwtTokenService;
        });

        // Merge module configuration
        $this->mergeConfigFrom(
            __DIR__.'/../Config/auth.php',
            'auth.jwt'
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../Config/auth.php' => config_path('auth/jwt.php'),
        ], 'auth-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Register middleware
        $this->app['router']->aliasMiddleware('jwt.auth', \Modules\Auth\Http\Middleware\JwtAuthMiddleware::class);
    }
}
