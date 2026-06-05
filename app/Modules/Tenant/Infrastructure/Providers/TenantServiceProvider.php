<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Application\Events\TenantCreated;
use Modules\Tenant\Application\Events\TenantStatusChanged;
use Modules\Core\Application\Contracts\CurrentTenantContextResolverInterface;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\Contracts\UseCases\Documents\TenantDocumentServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\Plans\CreateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\Plans\DeleteTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\Plans\GetTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\Plans\ListTenantPlansServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\Plans\UpdateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\SettingGroups\TenantSettingGroupServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\Settings\TenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\ActivateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\DeactivateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\GetTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\ListTenantsServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\SuspendTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UseCases\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Application\Support\TenantRecordMapper;
use Modules\Tenant\Application\UseCases\Domains\TenantDomainService as TenantDomainCrudService;
use Modules\Tenant\Application\UseCases\Documents\TenantDocumentService;
use Modules\Tenant\Application\UseCases\Plans\CreateTenantPlanService;
use Modules\Tenant\Application\UseCases\Plans\DeleteTenantPlanService;
use Modules\Tenant\Application\UseCases\Plans\GetTenantPlanService;
use Modules\Tenant\Application\UseCases\Plans\ListTenantPlansService;
use Modules\Tenant\Application\UseCases\Plans\UpdateTenantPlanService;
use Modules\Tenant\Application\UseCases\SettingGroups\TenantSettingGroupService;
use Modules\Tenant\Application\UseCases\Settings\TenantSettingService;
use Modules\Tenant\Application\UseCases\ActivateTenantService;
use Modules\Tenant\Application\UseCases\CreateTenantService;
use Modules\Tenant\Application\UseCases\DeactivateTenantService;
use Modules\Tenant\Application\UseCases\GetTenantService;
use Modules\Tenant\Application\UseCases\ListTenantsService;
use Modules\Tenant\Application\UseCases\SuspendTenantService;
use Modules\Tenant\Application\UseCases\UpdateTenantService;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Domain\Services\TenantDomainService;
use Modules\Tenant\Infrastructure\Listeners\RecordTenantLifecycleAuditListener;
use Modules\Tenant\Infrastructure\Listeners\SyncTenantIsolationContextListener;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDocumentModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDomainModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantPlanModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingGroupModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDocumentRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingGroupRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingRepository;
use Modules\Tenant\Infrastructure\Services\CurrentTenantContextResolver;
use Modules\Tenant\Presentation\Policies\TenantPolicy;
use Modules\Tenant\Presentation\Console\Commands\TenantActivateCommand;
use Modules\Tenant\Presentation\Console\Commands\TenantCreateCommand;
use Modules\Tenant\Presentation\Console\Commands\TenantDeactivateCommand;
use Modules\Tenant\Presentation\Console\Commands\TenantSuspendCommand;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/tenant.php', 'tenant');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
