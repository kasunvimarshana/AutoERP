<?php

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitAttachmentRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitAttachmentModel;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\MoveOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Services\CreateOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\UpdateOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\MoveOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\UploadOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitAttachmentService;

class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(OrganizationUnitRepositoryInterface::class, function ($app) {
            return new EloquentOrganizationUnitRepository($app->make(OrganizationUnitModel::class));
        });
        $this->app->bind(OrganizationUnitAttachmentRepositoryInterface::class, function ($app) {
            return new EloquentOrganizationUnitAttachmentRepository($app->make(OrganizationUnitAttachmentModel::class));
        });

        $this->app->bind(CreateOrganizationUnitServiceInterface::class, function ($app) {
            return new CreateOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(UpdateOrganizationUnitServiceInterface::class, function ($app) {
            return new UpdateOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(DeleteOrganizationUnitServiceInterface::class, function ($app) {
            return new DeleteOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(MoveOrganizationUnitServiceInterface::class, function ($app) {
            return new MoveOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(UploadOrganizationUnitAttachmentServiceInterface::class, function ($app) {
            return new UploadOrganizationUnitAttachmentService(
                $app->make(OrganizationUnitRepositoryInterface::class),
                $app->make(OrganizationUnitAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(DeleteOrganizationUnitAttachmentServiceInterface::class, function ($app) {
            return new DeleteOrganizationUnitAttachmentService(
                $app->make(OrganizationUnitAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class)
            );
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
