<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Services\CreateTenantService;
use Modules\Tenant\Application\Services\DeleteTenantAttachmentService;
use Modules\Tenant\Application\Services\DeleteTenantService;
use Modules\Tenant\Application\Services\UpdateTenantConfigService;
use Modules\Tenant\Application\Services\UpdateTenantService;
use Modules\Tenant\Application\Services\UploadTenantAttachmentService;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantAttachmentModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantAttachmentRepository;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories\EloquentTenantRepository;

class TenantServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(TenantRepositoryInterface::class, function ($app) {
            return new EloquentTenantRepository($app->make(TenantModel::class));
        });
        $this->app->bind(TenantAttachmentRepositoryInterface::class, function ($app) {
            return new EloquentTenantAttachmentRepository($app->make(TenantAttachmentModel::class));
        });

        $this->app->bind(CreateTenantServiceInterface::class, function ($app) {
            return new CreateTenantService($app->make(TenantRepositoryInterface::class));
        });
        $this->app->bind(UpdateTenantServiceInterface::class, function ($app) {
            return new UpdateTenantService($app->make(TenantRepositoryInterface::class));
        });
        $this->app->bind(DeleteTenantServiceInterface::class, function ($app) {
            return new DeleteTenantService($app->make(TenantRepositoryInterface::class));
        });
        $this->app->bind(UpdateTenantConfigServiceInterface::class, function ($app) {
            return new UpdateTenantConfigService($app->make(TenantRepositoryInterface::class));
        });
        $this->app->bind(UploadTenantAttachmentServiceInterface::class, function ($app) {
            return new UploadTenantAttachmentService(
                $app->make(TenantRepositoryInterface::class),
                $app->make(TenantAttachmentRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(DeleteTenantAttachmentServiceInterface::class, function ($app) {
            return new DeleteTenantAttachmentService(
                $app->make(TenantAttachmentRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });
    }

    public function boot()
    {
        // $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
