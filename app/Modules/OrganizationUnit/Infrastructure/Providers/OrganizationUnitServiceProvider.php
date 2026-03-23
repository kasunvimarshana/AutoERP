<?php

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitAttachmentRepository;
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
        $this->app->bind(OrganizationUnitRepositoryInterface::class, EloquentOrganizationUnitRepository::class);
        $this->app->bind(OrganizationUnitAttachmentRepositoryInterface::class, EloquentOrganizationUnitAttachmentRepository::class);

        $this->app->bind(CreateOrganizationUnitService::class, function ($app) {
            return new CreateOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(UpdateOrganizationUnitService::class, function ($app) {
            return new UpdateOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(DeleteOrganizationUnitService::class, function ($app) {
            return new DeleteOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(MoveOrganizationUnitService::class, function ($app) {
            return new MoveOrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
        $this->app->bind(UploadOrganizationUnitAttachmentService::class, function ($app) {
            return new UploadOrganizationUnitAttachmentService(
                $app->make(OrganizationUnitRepositoryInterface::class),
                $app->make(OrganizationUnitAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Services\FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(DeleteOrganizationUnitAttachmentService::class, function ($app) {
            return new DeleteOrganizationUnitAttachmentService(
                $app->make(OrganizationUnitAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Services\FileStorageServiceInterface::class)
            );
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
