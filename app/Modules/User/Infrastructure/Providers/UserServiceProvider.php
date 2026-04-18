<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserDeviceServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\SetUserPasswordServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Application\Contracts\UpsertUserDeviceServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use Modules\User\Application\Services\AssignRoleService;
use Modules\User\Application\Services\ChangePasswordService;
use Modules\User\Application\Services\CreatePermissionService;
use Modules\User\Application\Services\CreateRoleService;
use Modules\User\Application\Services\CreateUserService;
use Modules\User\Application\Services\DeleteUserDeviceService;
use Modules\User\Application\Services\DeletePermissionService;
use Modules\User\Application\Services\DeleteRoleService;
use Modules\User\Application\Services\DeleteUserAttachmentService;
use Modules\User\Application\Services\DeleteUserService;
use Modules\User\Application\Services\FindUserDevicesService;
use Modules\User\Application\Services\FindPermissionService;
use Modules\User\Application\Services\FindRoleService;
use Modules\User\Application\Services\FindUserAttachmentsService;
use Modules\User\Application\Services\FindUserService;
use Modules\User\Application\Services\SetUserPasswordService;
use Modules\User\Application\Services\SyncRolePermissionsService;
use Modules\User\Application\Services\UpsertUserDeviceService;
use Modules\User\Application\Services\UpdatePreferencesService;
use Modules\User\Application\Services\UpdateProfileService;
use Modules\User\Application\Services\UpdateUserService;
use Modules\User\Application\Services\UploadAvatarService;
use Modules\User\Application\Services\UploadUserAttachmentService;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserDeviceRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserAttachmentModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserAttachmentRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserDeviceRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;

class UserServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
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
        $this->app->bind(UserDeviceRepositoryInterface::class, function ($app) {
            return new EloquentUserDeviceRepository($app->make(UserDeviceModel::class));
        });

        $this->app->bind(CreateUserServiceInterface::class, function ($app) {
            return new CreateUserService(
                $app->make(UserRepositoryInterface::class),
                $app->make(RoleRepositoryInterface::class)
            );
        });
        $this->app->bind(FindUserServiceInterface::class, function ($app) {
            return new FindUserService($app->make(UserRepositoryInterface::class));
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
        $this->app->bind(UpdateProfileServiceInterface::class, function ($app) {
            return new UpdateProfileService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(ChangePasswordServiceInterface::class, function ($app) {
            return new ChangePasswordService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(SetUserPasswordServiceInterface::class, function ($app) {
            return new SetUserPasswordService($app->make(UserRepositoryInterface::class));
        });
        $this->app->bind(UploadAvatarServiceInterface::class, function ($app) {
            return new UploadAvatarService(
                $app->make(UserRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(UploadUserAttachmentServiceInterface::class, function ($app) {
            return new UploadUserAttachmentService(
                $app->make(UserRepositoryInterface::class),
                $app->make(UserAttachmentRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });
        $this->app->bind(DeleteUserAttachmentServiceInterface::class, function ($app) {
            return new DeleteUserAttachmentService(
                $app->make(UserAttachmentRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });

        $this->app->bind(FindUserAttachmentsServiceInterface::class, function ($app) {
            return new FindUserAttachmentsService(
                $app->make(UserAttachmentRepositoryInterface::class)
            );
        });
        $this->app->bind(FindUserDevicesServiceInterface::class, function ($app) {
            return new FindUserDevicesService(
                $app->make(UserDeviceRepositoryInterface::class)
            );
        });
        $this->app->bind(UpsertUserDeviceServiceInterface::class, function ($app) {
            return new UpsertUserDeviceService(
                $app->make(UserRepositoryInterface::class),
                $app->make(UserDeviceRepositoryInterface::class)
            );
        });
        $this->app->bind(DeleteUserDeviceServiceInterface::class, function ($app) {
            return new DeleteUserDeviceService(
                $app->make(UserDeviceRepositoryInterface::class)
            );
        });

        $this->app->bind(CreateRoleServiceInterface::class, function ($app) {
            return new CreateRoleService($app->make(RoleRepositoryInterface::class));
        });
        $this->app->bind(FindRoleServiceInterface::class, function ($app) {
            return new FindRoleService($app->make(RoleRepositoryInterface::class));
        });
        $this->app->bind(DeleteRoleServiceInterface::class, function ($app) {
            return new DeleteRoleService($app->make(RoleRepositoryInterface::class));
        });
        $this->app->bind(SyncRolePermissionsServiceInterface::class, function ($app) {
            return new SyncRolePermissionsService($app->make(RoleRepositoryInterface::class));
        });
        $this->app->bind(CreatePermissionServiceInterface::class, function ($app) {
            return new CreatePermissionService($app->make(PermissionRepositoryInterface::class));
        });
        $this->app->bind(FindPermissionServiceInterface::class, function ($app) {
            return new FindPermissionService($app->make(PermissionRepositoryInterface::class));
        });
        $this->app->bind(DeletePermissionServiceInterface::class, function ($app) {
            return new DeletePermissionService($app->make(PermissionRepositoryInterface::class));
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
