<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Application\Support\OrganizationUnitContext;
use Modules\OrganizationUnit\Domain\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Domain\Services\OrganizationUnitDomainService;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingGroupModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitDocumentRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitSettingGroupRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitSettingRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitTypeRepository;
use Modules\OrganizationUnit\Infrastructure\Services\CurrentOrganizationUnitContextResolver;
use Modules\OrganizationUnit\Presentation\Policies\OrganizationUnitPolicy;

final class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/organization-unit.php', 'organization-unit');

        $this->app->bind(
            'Modules\\Core\\Application\\Contracts\\CurrentOrganizationUnitContextResolverInterface',
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
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');

        Gate::policy(OrganizationUnitModel::class, OrganizationUnitPolicy::class);

    }
}
