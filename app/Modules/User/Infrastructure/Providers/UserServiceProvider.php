<?php

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\Application\Repositories\PermissionRepositoryInterface;
use Modules\User\Application\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Application\Repositories\RoleRepositoryInterface;
use Modules\User\Application\Repositories\UserDeviceRepositoryInterface;
use Modules\User\Application\Repositories\UserDocumentRepositoryInterface;
use Modules\User\Application\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\Repositories\UserRoleRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentRolePermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserDeviceRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserDocumentRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserPermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRoleRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserTenantRepository;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (
            [
                PermissionRepositoryInterface::class => EloquentPermissionRepository::class,
                RoleRepositoryInterface::class => EloquentRoleRepository::class,
                RolePermissionRepositoryInterface::class => EloquentRolePermissionRepository::class,
                UserDeviceRepositoryInterface::class => EloquentUserDeviceRepository::class,
                UserDocumentRepositoryInterface::class => EloquentUserDocumentRepository::class,
                UserRepositoryInterface::class => EloquentUserRepository::class,
                UserPermissionRepositoryInterface::class => EloquentUserPermissionRepository::class,
                UserRoleRepositoryInterface::class => EloquentUserRoleRepository::class,
                UserTenantRepositoryInterface::class => EloquentUserTenantRepository::class,
            ] as $interface => $implementation
        ) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
