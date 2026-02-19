<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Policies\OrganizationPolicy;
use Modules\Tenant\Policies\TenantPolicy;
use Modules\Tenant\Services\TenantContext;

/**
 * TenantServiceProvider
 *
 * Bootstraps the multi-tenancy module
 */
class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register TenantContext as a singleton
        $this->app->singleton(TenantContext::class, function ($app) {
            return new TenantContext;
        });

        // Merge module configuration
        $this->mergeConfigFrom(
            __DIR__.'/../Config/tenant.php',
            'tenant'
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../Config/tenant.php' => config_path('tenant.php'),
        ], 'tenant-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Register middleware
        $this->app['router']->aliasMiddleware('tenant', \Modules\Tenant\Http\Middleware\TenantMiddleware::class);

        // Register policies
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
    }
}
