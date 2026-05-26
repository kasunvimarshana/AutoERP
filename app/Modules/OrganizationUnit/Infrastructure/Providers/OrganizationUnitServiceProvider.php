<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\OrganizationUnit\Application\Contracts\UseCases\{
    OrganizationUnitDocuments\OrganizationUnitDocumentServiceInterface,
    OrganizationUnitSettingGroups\OrganizationUnitSettingGroupServiceInterface,
    OrganizationUnitSettings\OrganizationUnitSettingServiceInterface,
    OrganizationUnitTypes\OrganizationUnitTypeServiceInterface,
    OrganizationUnits\AssignUserToOrganizationUnitServiceInterface,
    OrganizationUnits\OrganizationUnitServiceInterface,
    OrganizationUnits\ResolveOrganizationUnitServiceInterface,
};
use Modules\OrganizationUnit\Application\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Application\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Application\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnitDocuments\OrganizationUnitDocumentService;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnitSettingGroups\OrganizationUnitSettingGroupService;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnitSettings\OrganizationUnitSettingService;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnitTypes\OrganizationUnitTypeService;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnits\AssignUserToOrganizationUnitService;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnits\OrganizationUnitService;
use Modules\OrganizationUnit\Application\UseCases\OrganizationUnits\ResolveOrganizationUnitService;
use Modules\OrganizationUnit\Application\Support\OrganizationUnitContext;
use Modules\OrganizationUnit\Domain\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Domain\Services\OrganizationUnitDomainService;
use Modules\OrganizationUnit\Infrastructure\Listeners\RecordOrganizationUnitAuditListener;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingGroupModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\{
    EloquentOrganizationUnitDocumentRepository,
    EloquentOrganizationUnitSettingGroupRepository,
    EloquentOrganizationUnitSettingRepository,
    EloquentOrganizationUnitTypeRepository,
};
use Modules\OrganizationUnit\Infrastructure\Services\CurrentOrganizationUnitContextResolver;
use Modules\OrganizationUnit\Presentation\Policies\OrganizationUnitPolicy;

final class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/organization-unit.php', 'organization-unit');

        $this->app->bind(
            'Modules\\Core\\Application\\Contracts\\CurrentOrganizationUnitContextResolverInterface',
            CurrentOrganizationUnitContextResolver::class,
        );

        $this->app->singleton(OrganizationUnitDomainServiceInterface::class, OrganizationUnitDomainService::class);
        $this->app->singleton(OrganizationUnitContext::class, OrganizationUnitContext::class);

        foreach (
            [
                OrganizationUnitTypeServiceInterface::class => OrganizationUnitTypeService::class,
                OrganizationUnitServiceInterface::class => OrganizationUnitService::class,
                ResolveOrganizationUnitServiceInterface::class => ResolveOrganizationUnitService::class,
                AssignUserToOrganizationUnitServiceInterface::class => AssignUserToOrganizationUnitService::class,
                OrganizationUnitSettingGroupServiceInterface::class => OrganizationUnitSettingGroupService::class,
                OrganizationUnitSettingServiceInterface::class => OrganizationUnitSettingService::class,
                OrganizationUnitDocumentServiceInterface::class => OrganizationUnitDocumentService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(
            OrganizationUnitTypeRepositoryInterface::class,
            function (): OrganizationUnitTypeRepositoryInterface {
                return new EloquentOrganizationUnitTypeRepository(new OrganizationUnitTypeModel());
            },
        );

        $this->app->singleton(
            OrganizationUnitRepositoryInterface::class,
            function (): OrganizationUnitRepositoryInterface {
                return new EloquentOrganizationUnitRepository(new OrganizationUnitModel());
            },
        );

        $this->app->singleton(
            OrganizationUnitSettingGroupRepositoryInterface::class,
            function (): OrganizationUnitSettingGroupRepositoryInterface {
                return new EloquentOrganizationUnitSettingGroupRepository(new OrganizationUnitSettingGroupModel());
            },
        );

        $this->app->singleton(
            OrganizationUnitSettingRepositoryInterface::class,
            function (): OrganizationUnitSettingRepositoryInterface {
                return new EloquentOrganizationUnitSettingRepository(new OrganizationUnitSettingModel());
            },
        );

        $this->app->singleton(
            OrganizationUnitDocumentRepositoryInterface::class,
            function (): OrganizationUnitDocumentRepositoryInterface {
                return new EloquentOrganizationUnitDocumentRepository(new OrganizationUnitDocumentModel());
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');

        Gate::policy(OrganizationUnitModel::class, OrganizationUnitPolicy::class);

        Event::listen(OrganizationUnitCreated::class, RecordOrganizationUnitAuditListener::class);
        Event::listen(OrganizationUnitUpdated::class, RecordOrganizationUnitAuditListener::class);
        Event::listen(OrganizationUnitDeleted::class, RecordOrganizationUnitAuditListener::class);
    }
}
