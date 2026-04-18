<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantPlansServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantSettingsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Factories\TenantConfigValueObjectFactory;
use Modules\Tenant\Application\Services\BulkUploadTenantAttachmentsService;
use Modules\Tenant\Application\Services\CreateTenantPlanService;
use Modules\Tenant\Application\Services\CreateTenantSettingService;
use Modules\Tenant\Application\Services\CreateTenantService;
use Modules\Tenant\Application\Services\DeleteTenantAttachmentService;
use Modules\Tenant\Application\Services\DeleteTenantPlanService;
use Modules\Tenant\Application\Services\DeleteTenantSettingService;
use Modules\Tenant\Application\Services\DeleteTenantService;
use Modules\Tenant\Application\Services\FindTenantAttachmentsService;
use Modules\Tenant\Application\Services\FindTenantPlansService;
use Modules\Tenant\Application\Services\FindTenantSettingsService;
use Modules\Tenant\Application\Services\FindTenantService;
use Modules\Tenant\Application\Services\UpdateTenantConfigService;
use Modules\Tenant\Application\Services\UpdateTenantPlanService;
use Modules\Tenant\Application\Services\UpdateTenantSettingService;
use Modules\Tenant\Application\Services\UpdateTenantService;
use Modules\Tenant\Application\Services\UploadTenantAttachmentService;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantAttachmentModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantPlanModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantAttachmentRepository;
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

        // Repositories
        $this->app->bind(TenantRepositoryInterface::class, function ($app) {
            return new EloquentTenantRepository($app->make(TenantModel::class));
        });
        $this->app->bind(TenantAttachmentRepositoryInterface::class, function ($app) {
            return new EloquentTenantAttachmentRepository($app->make(TenantAttachmentModel::class));
        });
        $this->app->bind(TenantPlanRepositoryInterface::class, function ($app) {
            return new EloquentTenantPlanRepository($app->make(TenantPlanModel::class));
        });
        $this->app->bind(TenantSettingRepositoryInterface::class, function ($app) {
            return new EloquentTenantSettingRepository($app->make(TenantSettingModel::class));
        });

        // Storage strategy (swappable)
        $this->app->bind(AttachmentStorageStrategyInterface::class, function ($app) {
            return new DefaultAttachmentStorageStrategy($app->make(FileStorageServiceInterface::class));
        });

        // Core tenant services
        $this->app->bind(CreateTenantServiceInterface::class, function ($app) {
            return new CreateTenantService(
                $app->make(TenantRepositoryInterface::class),
                $app->make(TenantConfigValueObjectFactory::class)
            );
        });
        $this->app->bind(UpdateTenantServiceInterface::class, function ($app) {
            return new UpdateTenantService(
                $app->make(TenantRepositoryInterface::class),
                $app->make(TenantConfigValueObjectFactory::class)
            );
        });
        $this->app->bind(DeleteTenantServiceInterface::class, function ($app) {
            return new DeleteTenantService($app->make(TenantRepositoryInterface::class));
        });
        $this->app->bind(UpdateTenantConfigServiceInterface::class, function ($app) {
            return new UpdateTenantConfigService($app->make(TenantRepositoryInterface::class));
        });

        $this->app->bind(FindTenantServiceInterface::class, function ($app) {
            return new FindTenantService($app->make(TenantRepositoryInterface::class));
        });
        $this->app->bind(FindTenantPlansServiceInterface::class, function ($app) {
            return new FindTenantPlansService($app->make(TenantPlanRepositoryInterface::class));
        });
        $this->app->bind(FindTenantSettingsServiceInterface::class, function ($app) {
            return new FindTenantSettingsService($app->make(TenantSettingRepositoryInterface::class));
        });
        $this->app->bind(CreateTenantPlanServiceInterface::class, function ($app) {
            return new CreateTenantPlanService($app->make(TenantPlanRepositoryInterface::class));
        });
        $this->app->bind(UpdateTenantPlanServiceInterface::class, function ($app) {
            return new UpdateTenantPlanService($app->make(TenantPlanRepositoryInterface::class));
        });
        $this->app->bind(DeleteTenantPlanServiceInterface::class, function ($app) {
            return new DeleteTenantPlanService($app->make(TenantPlanRepositoryInterface::class));
        });
        $this->app->bind(CreateTenantSettingServiceInterface::class, function ($app) {
            return new CreateTenantSettingService($app->make(TenantSettingRepositoryInterface::class));
        });
        $this->app->bind(UpdateTenantSettingServiceInterface::class, function ($app) {
            return new UpdateTenantSettingService($app->make(TenantSettingRepositoryInterface::class));
        });
        $this->app->bind(DeleteTenantSettingServiceInterface::class, function ($app) {
            return new DeleteTenantSettingService($app->make(TenantSettingRepositoryInterface::class));
        });

        // Attachment services
        $this->app->bind(FindTenantAttachmentsServiceInterface::class, function ($app) {
            return new FindTenantAttachmentsService($app->make(TenantAttachmentRepositoryInterface::class));
        });
        $this->app->bind(UploadTenantAttachmentServiceInterface::class, function ($app) {
            return new UploadTenantAttachmentService(
                $app->make(TenantRepositoryInterface::class),
                $app->make(TenantAttachmentRepositoryInterface::class),
                $app->make(AttachmentStorageStrategyInterface::class)
            );
        });
        $this->app->bind(BulkUploadTenantAttachmentsServiceInterface::class, function ($app) {
            return new BulkUploadTenantAttachmentsService(
                $app->make(TenantRepositoryInterface::class),
                $app->make(TenantAttachmentRepositoryInterface::class),
                $app->make(AttachmentStorageStrategyInterface::class)
            );
        });
        $this->app->bind(DeleteTenantAttachmentServiceInterface::class, function ($app) {
            return new DeleteTenantAttachmentService(
                $app->make(TenantAttachmentRepositoryInterface::class),
                $app->make(TenantRepositoryInterface::class),
                $app->make(AttachmentStorageStrategyInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
