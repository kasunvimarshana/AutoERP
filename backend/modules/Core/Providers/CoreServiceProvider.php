<?php

namespace Modules\Core\Providers;

use Modules\Core\Abstracts\BaseModuleServiceProvider;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CacheService;
use Modules\Core\Services\ConfigurationService;
use Modules\Core\Services\FeatureFlagService;
use Modules\Core\Services\LoggingService;
use Modules\Core\Services\TenantContext;
use Modules\Core\Services\TenantService;

class CoreServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'core';

    protected string $moduleName = 'Core System';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = [];

    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(TenantContext::class, function ($app) {
            return new TenantContext;
        });

        $this->app->singleton(AuditService::class, function ($app) {
            return new AuditService($app->make(TenantContext::class));
        });

        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService($app->make(TenantContext::class));
        });

        $this->app->singleton(ConfigurationService::class, function ($app) {
            return new ConfigurationService(
                $app->make(TenantContext::class),
                $app->make(CacheService::class)
            );
        });

        $this->app->singleton(FeatureFlagService::class, function ($app) {
            return new FeatureFlagService(
                $app->make(TenantContext::class),
                $app->make(CacheService::class)
            );
        });

        $this->app->singleton(LoggingService::class, function ($app) {
            return new LoggingService($app->make(TenantContext::class));
        });

        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService($app->make(TenantContext::class));
        });
    }

    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Load migrations
        $this->loadModuleMigrations();

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('tenant.identify', \Modules\Core\Http\Middleware\IdentifyTenant::class);
        $router->aliasMiddleware('tenant', \Modules\Core\Http\Middleware\RequireTenant::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleConfig(): array
    {
        return [
            'entities' => [
                'tenants' => [
                    'name' => 'Tenants',
                    'singular' => 'Tenant',
                    'icon' => 'office-building',
                    'routes' => [
                        'list' => '/core/tenants',
                        'create' => '/core/tenants/create',
                        'edit' => '/core/tenants/{id}/edit',
                        'view' => '/core/tenants/{id}',
                    ],
                ],
                'audit-logs' => [
                    'name' => 'Audit Logs',
                    'singular' => 'Audit Log',
                    'icon' => 'document-text',
                    'routes' => [
                        'list' => '/core/audit-logs',
                        'view' => '/core/audit-logs/{id}',
                    ],
                ],
            ],
            'features' => [
                'multi_tenant' => true,
                'audit_logging' => true,
                'feature_flags' => true,
                'caching' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'core.tenants.view',
            'core.tenants.create',
            'core.tenants.update',
            'core.tenants.delete',
            'core.audit-logs.view',
            'core.settings.view',
            'core.settings.update',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => '/api/core/tenants',
                'name' => 'core.tenants.index',
                'permission' => 'core.tenants.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/core/tenants',
                'name' => 'core.tenants.store',
                'permission' => 'core.tenants.create',
            ],
            [
                'method' => 'GET',
                'path' => '/api/core/audit-logs',
                'name' => 'core.audit-logs.index',
                'permission' => 'core.audit-logs.view',
            ],
        ];
    }
}
