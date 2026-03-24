<?php

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\Tenant\Infrastructure\Services\TenantConfigClient;
use Modules\Tenant\Application\Services\TenantConfigManager;

class TenantConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TenantConfigClientInterface::class, function ($app) {
            $tenantServiceUrl = config('tenant.tenant_service.url');
            $cacheTtl = config('tenant.tenant_config_cache_ttl', 300);
            return new TenantConfigClient($tenantServiceUrl, $cacheTtl);
        });

        $this->app->singleton(TenantConfigManagerInterface::class, TenantConfigManager::class);
    }

    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../../config/tenant.php' => config_path('tenant.php'),
        ], 'tenant-config');
    }
}
