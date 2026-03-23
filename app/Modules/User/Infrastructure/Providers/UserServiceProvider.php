<?php

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserAttachmentRepository;
use Modules\User\Application\Services\CreateUserService;
use Modules\User\Application\Services\UpdateUserService;
use Modules\User\Application\Services\DeleteUserService;
use Modules\User\Application\Services\AssignRoleService;
use Modules\User\Application\Services\UpdatePreferencesService;
use Modules\User\Application\Services\UploadUserAttachmentService;
use Modules\User\Application\Services\DeleteUserAttachmentService;

class UserServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, EloquentPermissionRepository::class);
        $this->app->bind(UserAttachmentRepositoryInterface::class, EloquentUserAttachmentRepository::class);

        $this->app->bind(CreateUserService::class, function ($app) {
            return new CreateUserService(
                $app->make(UserRepositoryInterface::class),
                $app->make(RoleRepositoryInterface::class)
            );
        });
        $this->app->bind(UpdateUserService::class, function ($app) {
            return new UpdateUserService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(DeleteUserService::class, function ($app) {
            return new DeleteUserService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(AssignRoleService::class, function ($app) {
            return new AssignRoleService(
                $app->make(UserRepositoryInterface::class),
                $app->make(RoleRepositoryInterface::class)
            );
        });
        $this->app->bind(UpdatePreferencesService::class, function ($app) {
            return new UpdatePreferencesService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(UploadUserAttachmentService::class, function ($app) {
            return new UploadUserAttachmentService(
                $app->make(UserRepositoryInterface::class),
                $app->make(UserAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Services\FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(DeleteUserAttachmentService::class, function ($app) {
            return new DeleteUserAttachmentService(
                $app->make(UserAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Services\FileStorageServiceInterface::class)
            );
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
