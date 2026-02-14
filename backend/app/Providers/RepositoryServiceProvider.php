<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 * 
 * Binds repository interfaces to their concrete implementations.
 * This allows for dependency injection and loose coupling.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Tenant Repository
        $this->app->bind(
            \App\Repositories\Contracts\TenantRepositoryInterface::class,
            \App\Repositories\Eloquent\TenantRepository::class
        );
        
        // Add more repository bindings here as we create them
        // Example:
        // $this->app->bind(
        //     \App\Repositories\Contracts\UserRepositoryInterface::class,
        //     \App\Repositories\Eloquent\UserRepository::class
        // );
    }

    /**
     * Bootstrap services.
     */
    public function bootstrap(): void
    {
        //
    }
}
