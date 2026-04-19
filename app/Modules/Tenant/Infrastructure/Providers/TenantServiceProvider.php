<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantDomainsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantPlansServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantSettingsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Factories\TenantConfigValueObjectFactory;
use Modules\Tenant\Application\Services\BulkUploadTenantAttachmentsService;
use Modules\Tenant\Application\Services\CreateTenantPlanService;
use Modules\Tenant\Application\Services\CreateTenantDomainService;
use Modules\Tenant\Application\Services\CreateTenantSettingService;
use Modules\Tenant\Application\Services\CreateTenantService;
use Modules\Tenant\Application\Services\DeleteTenantDomainService;
use Modules\Tenant\Application\Services\DeleteTenantAttachmentService;
use Modules\Tenant\Application\Services\DeleteTenantPlanService;
use Modules\Tenant\Application\Services\DeleteTenantSettingService;
use Modules\Tenant\Application\Services\DeleteTenantService;
use Modules\Tenant\Application\Services\FindTenantDomainsService;
use Modules\Tenant\Application\Services\FindTenantAttachmentsService;
use Modules\Tenant\Application\Services\FindTenantPlansService;
use Modules\Tenant\Application\Services\FindTenantSettingsService;
use Modules\Tenant\Application\Services\FindTenantService;
use Modules\Tenant\Application\Services\UpdateTenantDomainService;
use Modules\Tenant\Application\Services\UpdateTenantConfigService;
use Modules\Tenant\Application\Services\UpdateTenantPlanService;
use Modules\Tenant\Application\Services\UpdateTenantSettingService;
use Modules\Tenant\Application\Services\UpdateTenantService;
use Modules\Tenant\Application\Services\UploadTenantAttachmentService;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantAttachmentRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantSettingRepository;
use Modules\Tenant\Infrastructure\Storage\DefaultAttachmentStorageStrategy;

class TenantServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->singleton(TenantConfigValueObjectFactory::class);

        $repositoryBindings = [
            TenantRepositoryInterface::class => EloquentTenantRepository::class,
            TenantAttachmentRepositoryInterface::class => EloquentTenantAttachmentRepository::class,
            TenantPlanRepositoryInterface::class => EloquentTenantPlanRepository::class,
            TenantSettingRepositoryInterface::class => EloquentTenantSettingRepository::class,
            TenantDomainRepositoryInterface::class => EloquentTenantDomainRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $this->app->bind(AttachmentStorageStrategyInterface::class, DefaultAttachmentStorageStrategy::class);

        $serviceBindings = [
            CreateTenantServiceInterface::class => CreateTenantService::class,
            UpdateTenantServiceInterface::class => UpdateTenantService::class,
            DeleteTenantServiceInterface::class => DeleteTenantService::class,
            UpdateTenantConfigServiceInterface::class => UpdateTenantConfigService::class,
            FindTenantServiceInterface::class => FindTenantService::class,
            FindTenantPlansServiceInterface::class => FindTenantPlansService::class,
            FindTenantSettingsServiceInterface::class => FindTenantSettingsService::class,
            FindTenantDomainsServiceInterface::class => FindTenantDomainsService::class,
            CreateTenantPlanServiceInterface::class => CreateTenantPlanService::class,
            UpdateTenantPlanServiceInterface::class => UpdateTenantPlanService::class,
            DeleteTenantPlanServiceInterface::class => DeleteTenantPlanService::class,
            CreateTenantSettingServiceInterface::class => CreateTenantSettingService::class,
            UpdateTenantSettingServiceInterface::class => UpdateTenantSettingService::class,
            DeleteTenantSettingServiceInterface::class => DeleteTenantSettingService::class,
            CreateTenantDomainServiceInterface::class => CreateTenantDomainService::class,
            UpdateTenantDomainServiceInterface::class => UpdateTenantDomainService::class,
            DeleteTenantDomainServiceInterface::class => DeleteTenantDomainService::class,
            FindTenantAttachmentsServiceInterface::class => FindTenantAttachmentsService::class,
            UploadTenantAttachmentServiceInterface::class => UploadTenantAttachmentService::class,
            BulkUploadTenantAttachmentsServiceInterface::class => BulkUploadTenantAttachmentsService::class,
            DeleteTenantAttachmentServiceInterface::class => DeleteTenantAttachmentService::class,
        ];

        foreach ($serviceBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
