<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\CurrentOrganizationUnitContextResolverInterface;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Modules\OrganizationUnit\Contracts\OrganizationUnitBrandingReaderInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitHierarchyReaderInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitOwnershipCheckerInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitPopulationReaderInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Services\CurrentOrganizationUnitContextResolver;
use Modules\OrganizationUnit\Services\Hierarchy\OrganizationUnitHierarchyReader;
use Modules\OrganizationUnit\Services\Hierarchy\OrganizationUnitPopulationReader;
use Modules\OrganizationUnit\Services\Lifecycle\OrganizationUnitLifecycleGuard;
use Modules\OrganizationUnit\Services\OrganizationUnitOwnershipChecker;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationHierarchyService;
use Modules\OrganizationUnit\Services\Provisioning\TenantOrganizationProvisioner;
use Modules\OrganizationUnit\Services\Rules\OrganizationUnitDomainService;
use Modules\OrganizationUnit\Services\Storage\OrganizationUnitBrandingReader;
use Modules\OrganizationUnit\Services\TenantLimits\OrganizationUnitLimitUsageContributor;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/organization-unit.php', 'organization-unit');
        $this->app->tag([OrganizationUnitLimitUsageContributor::class], 'tenant.limit_usage');
        $this->app->bind(CurrentOrganizationUnitContextResolverInterface::class, CurrentOrganizationUnitContextResolver::class);
        $this->app->singleton(OrganizationUnitDomainServiceInterface::class, OrganizationUnitDomainService::class);
        $this->app->singleton(OrganizationUnitOwnershipCheckerInterface::class, OrganizationUnitOwnershipChecker::class);
        $this->app->singleton(OrganizationUnitHierarchyReaderInterface::class, OrganizationUnitHierarchyReader::class);
        $this->app->singleton(OrganizationUnitPopulationReaderInterface::class, OrganizationUnitPopulationReader::class);
        $this->app->singleton(OrganizationUnitBrandingReaderInterface::class, OrganizationUnitBrandingReader::class);
        $this->app->singleton(OrganizationUnitContext::class);
        $this->app->singleton(OrganizationHierarchyService::class);
        $this->app->singleton(
            OrganizationUnitLifecycleGuard::class,
            fn ($app): OrganizationUnitLifecycleGuard => new OrganizationUnitLifecycleGuard(
                $app->tagged('organization-unit.lifecycle_blocker'),
            ),
        );
        $this->app->singleton(TenantOrganizationProvisionerInterface::class, TenantOrganizationProvisioner::class);
        $this->app->singleton(OrganizationUnitRepositoryInterface::class, fn (): OrganizationUnitRepositoryInterface => new EloquentOrganizationUnitRepository(new OrganizationUnitModel));
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('organization-unit', OrganizationUnitPermission::descriptions());
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
