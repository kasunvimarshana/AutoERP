<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUserAdded;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUserRemoved;
use Modules\OrganizationUnit\Infrastructure\Listeners\HandleOrganizationUnitCreated;
use Modules\OrganizationUnit\Infrastructure\Listeners\HandleOrganizationUnitDeleted;
use Modules\OrganizationUnit\Infrastructure\Listeners\HandleOrganizationUnitUpdated;
use Modules\OrganizationUnit\Infrastructure\Listeners\HandleOrganizationUnitUserAdded;
use Modules\OrganizationUnit\Infrastructure\Listeners\HandleOrganizationUnitUserRemoved;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Services\CreateOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\CreateOrganizationUnitTypeService;
use Modules\OrganizationUnit\Application\Services\CreateOrganizationUnitUserService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitTypeService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitUserService;
use Modules\OrganizationUnit\Application\Services\FindOrganizationUnitAttachmentsService;
use Modules\OrganizationUnit\Application\Services\FindOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\FindOrganizationUnitTypeService;
use Modules\OrganizationUnit\Application\Services\FindOrganizationUnitUserService;
use Modules\OrganizationUnit\Application\Services\UpdateOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\UpdateOrganizationUnitTypeService;
use Modules\OrganizationUnit\Application\Services\UpdateOrganizationUnitUserService;
use Modules\OrganizationUnit\Application\Services\UploadOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitAttachmentRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitTypeRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitUserRepository;

class OrganizationUnitServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            OrganizationUnitRepositoryInterface::class => EloquentOrganizationUnitRepository::class,
            OrganizationUnitAttachmentRepositoryInterface::class => EloquentOrganizationUnitAttachmentRepository::class,
            OrganizationUnitTypeRepositoryInterface::class => EloquentOrganizationUnitTypeRepository::class,
            OrganizationUnitUserRepositoryInterface::class => EloquentOrganizationUnitUserRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $serviceBindings = [
            CreateOrganizationUnitServiceInterface::class => CreateOrganizationUnitService::class,
            FindOrganizationUnitServiceInterface::class => FindOrganizationUnitService::class,
            UpdateOrganizationUnitServiceInterface::class => UpdateOrganizationUnitService::class,
            DeleteOrganizationUnitServiceInterface::class => DeleteOrganizationUnitService::class,
            CreateOrganizationUnitTypeServiceInterface::class => CreateOrganizationUnitTypeService::class,
            FindOrganizationUnitTypeServiceInterface::class => FindOrganizationUnitTypeService::class,
            UpdateOrganizationUnitTypeServiceInterface::class => UpdateOrganizationUnitTypeService::class,
            DeleteOrganizationUnitTypeServiceInterface::class => DeleteOrganizationUnitTypeService::class,
            CreateOrganizationUnitUserServiceInterface::class => CreateOrganizationUnitUserService::class,
            FindOrganizationUnitUserServiceInterface::class => FindOrganizationUnitUserService::class,
            UpdateOrganizationUnitUserServiceInterface::class => UpdateOrganizationUnitUserService::class,
            DeleteOrganizationUnitUserServiceInterface::class => DeleteOrganizationUnitUserService::class,
            FindOrganizationUnitAttachmentsServiceInterface::class => FindOrganizationUnitAttachmentsService::class,
            UploadOrganizationUnitAttachmentServiceInterface::class => UploadOrganizationUnitAttachmentService::class,
            DeleteOrganizationUnitAttachmentServiceInterface::class => DeleteOrganizationUnitAttachmentService::class,
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

        Event::listen(OrganizationUnitCreated::class, HandleOrganizationUnitCreated::class);
        Event::listen(OrganizationUnitUpdated::class, HandleOrganizationUnitUpdated::class);
        Event::listen(OrganizationUnitDeleted::class, HandleOrganizationUnitDeleted::class);
        Event::listen(OrganizationUnitUserAdded::class, HandleOrganizationUnitUserAdded::class);
        Event::listen(OrganizationUnitUserRemoved::class, HandleOrganizationUnitUserRemoved::class);
    }
}
