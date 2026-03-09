<?php

namespace App\Providers;

use App\Infrastructure\Tenant\TenantDatabaseManager;
use App\Infrastructure\Tenant\TenantResolver;
use App\Services\RuntimeConfigService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register tenant-related bindings.
     */
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class);
        $this->app->singleton(TenantDatabaseManager::class);
    }

    /**
     * Bootstrap tenant services.
     */
    public function boot(): void
    {
        // Register tenant middleware as a named middleware alias
        $this->app->make(Kernel::class)->prependMiddlewareToGroup(
            'api',
            \App\Http\Middleware\TenantMiddleware::class
        );

        // When resolved, load and apply tenant-specific runtime config
        $this->app->resolving(
            \App\Domain\Models\Tenant::class,
            function (\App\Domain\Models\Tenant $tenant, $app) {
                /** @var RuntimeConfigService $configService */
                $configService = $app->make(RuntimeConfigService::class);
                $configService->loadForTenant($tenant);
            }
        );
    }
}
