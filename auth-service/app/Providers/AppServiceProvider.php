<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AuthorizationServiceInterface;
use App\Contracts\AuthServiceInterface;
use App\Contracts\TenantServiceInterface;
use App\Services\AuthorizationService;
use App\Services\AuthService;
use App\Services\TenantService;
use Illuminate\Support\ServiceProvider;

/**
 * Binds service interfaces to their concrete implementations.
 *
 * Using interfaces here means we can swap implementations
 * (e.g., switch from Passport to Sanctum) without touching
 * any consuming code.
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register interface-to-implementation bindings.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(TenantServiceInterface::class, TenantService::class);
        $this->app->bind(AuthorizationServiceInterface::class, AuthorizationService::class);
    }

    public function boot(): void
    {
        //
    }
}
