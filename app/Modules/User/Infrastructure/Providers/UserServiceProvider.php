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
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserAttachmentModel;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
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
        $this->app->bind(UserRepositoryInterface::class, function ($app) {
            return new EloquentUserRepository($app->make(UserModel::class));
        });
        $this->app->bind(RoleRepositoryInterface::class, function ($app) {
            return new EloquentRoleRepository($app->make(RoleModel::class));
        });
        $this->app->bind(PermissionRepositoryInterface::class, function ($app) {
            return new EloquentPermissionRepository($app->make(PermissionModel::class));
        });
        $this->app->bind(UserAttachmentRepositoryInterface::class, function ($app) {
            return new EloquentUserAttachmentRepository($app->make(UserAttachmentModel::class));
        });

        $this->app->bind(CreateUserServiceInterface::class, function ($app) {
            return new CreateUserService(
                $app->make(UserRepositoryInterface::class),
                $app->make(RoleRepositoryInterface::class)
            );
        });
        $this->app->bind(UpdateUserServiceInterface::class, function ($app) {
            return new UpdateUserService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(DeleteUserServiceInterface::class, function ($app) {
            return new DeleteUserService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(AssignRoleServiceInterface::class, function ($app) {
            return new AssignRoleService(
                $app->make(UserRepositoryInterface::class),
                $app->make(RoleRepositoryInterface::class)
            );
        });
        $this->app->bind(UpdatePreferencesServiceInterface::class, function ($app) {
            return new UpdatePreferencesService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(UploadUserAttachmentServiceInterface::class, function ($app) {
            return new UploadUserAttachmentService(
                $app->make(UserRepositoryInterface::class),
                $app->make(UserAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(DeleteUserAttachmentServiceInterface::class, function ($app) {
            return new DeleteUserAttachmentService(
                $app->make(UserAttachmentRepositoryInterface::class),
                $app->make(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class)
            );
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
