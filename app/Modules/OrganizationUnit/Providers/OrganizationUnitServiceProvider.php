<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\CurrentOrganizationUnitContextResolverInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitOwnershipCheckerInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Policies\OrganizationUnitPolicy;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitDocumentRepository;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitTypeRepository;
use Modules\OrganizationUnit\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Services\CurrentOrganizationUnitContextResolver;
use Modules\OrganizationUnit\Services\OrganizationUnitOwnershipChecker;
use Modules\OrganizationUnit\Services\Rules\OrganizationUnitDomainService;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;

final class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/organization-unit.php', 'organization-unit');
        $this->app->bind(CurrentOrganizationUnitContextResolverInterface::class, CurrentOrganizationUnitContextResolver::class);
        $this->app->singleton(OrganizationUnitDomainServiceInterface::class, OrganizationUnitDomainService::class);
        $this->app->singleton(OrganizationUnitOwnershipCheckerInterface::class, OrganizationUnitOwnershipChecker::class);
        $this->app->singleton(OrganizationUnitContext::class, OrganizationUnitContext::class);
        $this->app->singleton(OrganizationUnitTypeRepositoryInterface::class, fn (): OrganizationUnitTypeRepositoryInterface => new EloquentOrganizationUnitTypeRepository(new OrganizationUnitTypeModel));
        $this->app->singleton(OrganizationUnitRepositoryInterface::class, fn (): OrganizationUnitRepositoryInterface => new EloquentOrganizationUnitRepository(new OrganizationUnitModel));
        $this->app->singleton(OrganizationUnitDocumentRepositoryInterface::class, fn (): OrganizationUnitDocumentRepositoryInterface => new EloquentOrganizationUnitDocumentRepository(new OrganizationUnitDocumentModel));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Gate::policy(OrganizationUnitModel::class, OrganizationUnitPolicy::class);
    }
}
