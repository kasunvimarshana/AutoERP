<?php
namespace Modules\Authorization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Application\Contracts\AssignUserRoleServiceInterface;
use Modules\Authorization\Application\Contracts\CreatePermissionServiceInterface;
use Modules\Authorization\Application\Contracts\CreateRoleServiceInterface;
use Modules\Authorization\Application\Contracts\DeletePermissionServiceInterface;
use Modules\Authorization\Application\Contracts\DeleteRoleServiceInterface;
use Modules\Authorization\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\Authorization\Application\Services\AssignUserRoleService;
use Modules\Authorization\Application\Services\CreatePermissionService;
use Modules\Authorization\Application\Services\CreateRoleService;
use Modules\Authorization\Application\Services\DeletePermissionService;
use Modules\Authorization\Application\Services\DeleteRoleService;
use Modules\Authorization\Application\Services\SyncRolePermissionsService;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRoleRepository;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, EloquentPermissionRepository::class);
        $this->app->bind(UserRoleRepositoryInterface::class, EloquentUserRoleRepository::class);

        $this->app->bind(CreateRoleServiceInterface::class, CreateRoleService::class);
        $this->app->bind(DeleteRoleServiceInterface::class, DeleteRoleService::class);
        $this->app->bind(SyncRolePermissionsServiceInterface::class, SyncRolePermissionsService::class);
        $this->app->bind(CreatePermissionServiceInterface::class, CreatePermissionService::class);
        $this->app->bind(DeletePermissionServiceInterface::class, DeletePermissionService::class);
        $this->app->bind(AssignUserRoleServiceInterface::class, AssignUserRoleService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
