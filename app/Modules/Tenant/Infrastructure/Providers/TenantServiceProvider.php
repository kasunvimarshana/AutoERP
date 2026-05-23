<?php

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Application\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDocumentRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingGroupRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingRepository;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            TenantDocumentRepositoryInterface::class => EloquentTenantDocumentRepository::class,
            TenantDomainRepositoryInterface::class => EloquentTenantDomainRepository::class,
            TenantRepositoryInterface::class => EloquentTenantRepository::class,
            TenantPlanRepositoryInterface::class => EloquentTenantPlanRepository::class,
            TenantSettingGroupRepositoryInterface::class => EloquentTenantSettingGroupRepository::class,
            TenantSettingRepositoryInterface::class => EloquentTenantSettingRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
