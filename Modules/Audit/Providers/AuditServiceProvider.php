<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Policies\AuditLogPolicy;
use Modules\Audit\Services\AuditService;

/**
 * AuditServiceProvider
 *
 * Bootstraps the audit logging module
 */
class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(AuditService::class, function ($app) {
            return new AuditService(
                $app->make(\Modules\Tenant\Services\TenantContext::class)
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
