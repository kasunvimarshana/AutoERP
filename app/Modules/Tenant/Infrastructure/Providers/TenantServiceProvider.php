<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\CurrentTenantContextResolverInterface;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Application\Support\TenantRecordMapper;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Domain\Services\TenantDomainService;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDocumentModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDomainModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantPlanModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingGroupModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDocumentRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingGroupRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingRepository;
use Modules\Tenant\Infrastructure\Services\CurrentTenantContextResolver;
use Modules\Tenant\Presentation\Console\Commands\TenantActivateCommand;
use Modules\Tenant\Presentation\Console\Commands\TenantCreateCommand;
use Modules\Tenant\Presentation\Console\Commands\TenantDeactivateCommand;
use Modules\Tenant\Presentation\Console\Commands\TenantSuspendCommand;
use Modules\Tenant\Presentation\Policies\TenantPolicy;

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
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');

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
