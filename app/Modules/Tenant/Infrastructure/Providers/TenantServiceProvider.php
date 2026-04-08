<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Application\Contracts\OrgUnitServiceInterface;
use Modules\Tenant\Application\Contracts\TenantServiceInterface;
use Modules\Tenant\Application\Services\OrgUnitService;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrgUnitRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantRepository;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantRepositoryInterface::class, function ($app) {
            return new EloquentTenantRepository($app->make(TenantModel::class));
        });

        $this->app->bind(OrgUnitRepositoryInterface::class, function ($app) {
            return new EloquentOrgUnitRepository($app->make(OrgUnitModel::class));
        });

        $this->app->bind(TenantServiceInterface::class, function ($app) {
            return new TenantService($app->make(TenantRepositoryInterface::class));
        });

        $this->app->bind(OrgUnitServiceInterface::class, function ($app) {
            return new OrgUnitService($app->make(OrgUnitRepositoryInterface::class));
        });

        $this->mergeConfigFrom(__DIR__.'/../../config/tenant.php', 'tenant');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishes([
            __DIR__.'/../../config/tenant.php' => config_path('tenant.php'),
        ], 'tenant-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'tenant-migrations');
    }
}
