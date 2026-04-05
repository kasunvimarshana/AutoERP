<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Application\Contracts\PermissionServiceInterface;
use Modules\Authorization\Application\Contracts\RoleServiceInterface;
use Modules\Authorization\Application\Contracts\UserRoleServiceInterface;
use Modules\Authorization\Application\Services\PermissionService;
use Modules\Authorization\Application\Services\RoleService;
use Modules\Authorization\Application\Services\UserRoleService;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRoleRepository;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoleRepositoryInterface::class, fn($app) =>
            new EloquentRoleRepository($app->make(RoleModel::class))
        );
        $this->app->bind(PermissionRepositoryInterface::class, fn($app) =>
            new EloquentPermissionRepository($app->make(PermissionModel::class))
        );
        $this->app->bind(UserRoleRepositoryInterface::class, fn($app) =>
            new EloquentUserRoleRepository($app->make(UserRoleModel::class), $app->make(RoleModel::class))
        );
        $this->app->bind(RoleServiceInterface::class, fn($app) =>
            new RoleService($app->make(RoleRepositoryInterface::class))
        );
        $this->app->bind(PermissionServiceInterface::class, fn($app) =>
            new PermissionService($app->make(PermissionRepositoryInterface::class))
        );
        $this->app->bind(UserRoleServiceInterface::class, fn($app) =>
            new UserRoleService($app->make(UserRoleRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
