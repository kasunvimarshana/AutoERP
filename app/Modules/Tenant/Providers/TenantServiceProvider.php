<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Tenant\Console\Commands\TenantActivateCommand;
use Modules\Tenant\Console\Commands\TenantCreateCommand;
use Modules\Tenant\Console\Commands\TenantDeactivateCommand;
use Modules\Tenant\Console\Commands\TenantSuspendCommand;
use Modules\Tenant\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Models\TenantDocumentModel;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Models\TenantSettingGroupModel;
use Modules\Tenant\Models\TenantSettingModel;
use Modules\Tenant\Policies\TenantPolicy;
use Modules\Tenant\Repositories\EloquentTenantDocumentRepository;
use Modules\Tenant\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Repositories\EloquentTenantRepository;
use Modules\Tenant\Repositories\EloquentTenantSettingGroupRepository;
use Modules\Tenant\Repositories\EloquentTenantSettingRepository;
use Modules\Tenant\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Tenant\Services\Rules\TenantDomainService;
use Modules\Tenant\Support\TenantRecordMapper;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tenant.php', 'tenant');

        $this->app->bind(CurrentTenantContextResolverInterface::class, CurrentTenantContextResolver::class);
        $this->app->singleton(TenantDomainServiceInterface::class, TenantDomainService::class);
        $this->app->singleton(TenantRecordMapperInterface::class, TenantRecordMapper::class);
        $this->app->singleton(TenantRepositoryInterface::class, function (): TenantRepositoryInterface {
            return new EloquentTenantRepository(new TenantModel);
        });

        $this->app->singleton(TenantPlanRepositoryInterface::class, function (): TenantPlanRepositoryInterface {
            return new EloquentTenantPlanRepository(new TenantPlanModel);
        });

        $this->app->singleton(
            TenantSettingGroupRepositoryInterface::class,
            function (): TenantSettingGroupRepositoryInterface {
                return new EloquentTenantSettingGroupRepository(new TenantSettingGroupModel);
            },
        );

        $this->app->singleton(TenantSettingRepositoryInterface::class, function (): TenantSettingRepositoryInterface {
            return new EloquentTenantSettingRepository(new TenantSettingModel);
        });

        $this->app->singleton(TenantDocumentRepositoryInterface::class, function (): TenantDocumentRepositoryInterface {
            return new EloquentTenantDocumentRepository(new TenantDocumentModel);
        });

        $this->app->singleton(TenantDomainRepositoryInterface::class, function (): TenantDomainRepositoryInterface {
            return new EloquentTenantDomainRepository(new TenantDomainModel);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Gate::policy(TenantModel::class, TenantPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantCreateCommand::class,
                TenantActivateCommand::class,
                TenantSuspendCommand::class,
                TenantDeactivateCommand::class,
            ]);
        }
    }
}
