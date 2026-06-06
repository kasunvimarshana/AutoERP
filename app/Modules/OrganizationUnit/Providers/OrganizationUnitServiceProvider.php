<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\OrganizationUnit\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitSettingGroupModel;
use Modules\OrganizationUnit\Models\OrganizationUnitSettingModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Policies\OrganizationUnitPolicy;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitDocumentRepository;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitSettingGroupRepository;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitSettingRepository;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitTypeRepository;
use Modules\OrganizationUnit\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Services\CurrentOrganizationUnitContextResolver;
use Modules\OrganizationUnit\Services\Rules\OrganizationUnitDomainService;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;

final class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/organization-unit.php', 'organization-unit');

        $this->app->bind(
            'Modules\\Core\\Contracts\\CurrentOrganizationUnitContextResolverInterface',
            CurrentOrganizationUnitContextResolver::class,
        );

        $this->app->singleton(OrganizationUnitDomainServiceInterface::class, OrganizationUnitDomainService::class);
        $this->app->singleton(OrganizationUnitContext::class, OrganizationUnitContext::class);
        $this->app->singleton(
            OrganizationUnitTypeRepositoryInterface::class,
            function (): OrganizationUnitTypeRepositoryInterface {
                return new EloquentOrganizationUnitTypeRepository(new OrganizationUnitTypeModel);
            },
        );

        $this->app->singleton(
            OrganizationUnitRepositoryInterface::class,
            function (): OrganizationUnitRepositoryInterface {
                return new EloquentOrganizationUnitRepository(new OrganizationUnitModel);
            },
        );

        $this->app->singleton(
            OrganizationUnitSettingGroupRepositoryInterface::class,
            function (): OrganizationUnitSettingGroupRepositoryInterface {
                return new EloquentOrganizationUnitSettingGroupRepository(new OrganizationUnitSettingGroupModel);
            },
        );

        $this->app->singleton(
            OrganizationUnitSettingRepositoryInterface::class,
            function (): OrganizationUnitSettingRepositoryInterface {
                return new EloquentOrganizationUnitSettingRepository(new OrganizationUnitSettingModel);
            },
        );

        $this->app->singleton(
            OrganizationUnitDocumentRepositoryInterface::class,
            function (): OrganizationUnitDocumentRepositoryInterface {
                return new EloquentOrganizationUnitDocumentRepository(new OrganizationUnitDocumentModel);
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Gate::policy(OrganizationUnitModel::class, OrganizationUnitPolicy::class);

    }
}
