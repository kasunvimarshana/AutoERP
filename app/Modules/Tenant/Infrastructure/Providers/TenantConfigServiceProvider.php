<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\Tenant\Application\Services\TenantConfigManager;
use Modules\Tenant\Infrastructure\Services\TenantConfigClient;

class TenantConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantConfigClientInterface::class, TenantConfigClient::class);
        $this->app->when(TenantConfigClient::class)
            ->needs('$cacheTtl')
            ->give(static fn (): int => (int) config('tenant.tenant_config_cache_ttl', 300));

        $this->app->singleton(TenantConfigManagerInterface::class, TenantConfigManager::class);
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../../config/tenant.php' => config_path('tenant.php'),
        ], 'tenant-config');
    }
}
