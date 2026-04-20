<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\DeleteUserDeviceServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\SetUserPasswordServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\UpsertUserDeviceServiceInterface;
use Modules\User\Application\Services\AssignRoleService;
use Modules\User\Application\Services\ChangePasswordService;
use Modules\User\Application\Services\CreatePermissionService;
use Modules\User\Application\Services\CreateRoleService;
use Modules\User\Application\Services\CreateUserService;
use Modules\User\Application\Services\DeletePermissionService;
use Modules\User\Application\Services\DeleteRoleService;
use Modules\User\Application\Services\DeleteUserAttachmentService;
use Modules\User\Application\Services\DeleteUserDeviceService;
use Modules\User\Application\Services\DeleteUserService;
use Modules\User\Application\Services\FindPermissionService;
use Modules\User\Application\Services\FindRoleService;
use Modules\User\Application\Services\FindUserAttachmentsService;
use Modules\User\Application\Services\FindUserDevicesService;
use Modules\User\Application\Services\FindUserService;
use Modules\User\Application\Services\SetUserPasswordService;
use Modules\User\Application\Services\SyncRolePermissionsService;
use Modules\User\Application\Services\UpdatePreferencesService;
use Modules\User\Application\Services\UpdateProfileService;
use Modules\User\Application\Services\UpdateUserService;
use Modules\User\Application\Services\UploadAvatarService;
use Modules\User\Application\Services\UploadUserAttachmentService;
use Modules\User\Application\Services\UpsertUserDeviceService;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserDeviceRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
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
        $repositoryBindings = [
            UserRepositoryInterface::class => EloquentUserRepository::class,
            RoleRepositoryInterface::class => EloquentRoleRepository::class,
            PermissionRepositoryInterface::class => EloquentPermissionRepository::class,
            UserAttachmentRepositoryInterface::class => EloquentUserAttachmentRepository::class,
            UserDeviceRepositoryInterface::class => EloquentUserDeviceRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $serviceBindings = [
            CreateUserServiceInterface::class => CreateUserService::class,
            FindUserServiceInterface::class => FindUserService::class,
            UpdateUserServiceInterface::class => UpdateUserService::class,
            DeleteUserServiceInterface::class => DeleteUserService::class,
            AssignRoleServiceInterface::class => AssignRoleService::class,
            UpdatePreferencesServiceInterface::class => UpdatePreferencesService::class,
            UpdateProfileServiceInterface::class => UpdateProfileService::class,
            ChangePasswordServiceInterface::class => ChangePasswordService::class,
            SetUserPasswordServiceInterface::class => SetUserPasswordService::class,
            UploadAvatarServiceInterface::class => UploadAvatarService::class,
            UploadUserAttachmentServiceInterface::class => UploadUserAttachmentService::class,
            DeleteUserAttachmentServiceInterface::class => DeleteUserAttachmentService::class,
            FindUserAttachmentsServiceInterface::class => FindUserAttachmentsService::class,
            FindUserDevicesServiceInterface::class => FindUserDevicesService::class,
            UpsertUserDeviceServiceInterface::class => UpsertUserDeviceService::class,
            DeleteUserDeviceServiceInterface::class => DeleteUserDeviceService::class,
            CreateRoleServiceInterface::class => CreateRoleService::class,
            FindRoleServiceInterface::class => FindRoleService::class,
            DeleteRoleServiceInterface::class => DeleteRoleService::class,
            SyncRolePermissionsServiceInterface::class => SyncRolePermissionsService::class,
            CreatePermissionServiceInterface::class => CreatePermissionService::class,
            FindPermissionServiceInterface::class => FindPermissionService::class,
            DeletePermissionServiceInterface::class => DeletePermissionService::class,
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
