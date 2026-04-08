<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Application\Contracts\AuthServiceInterface;
use Modules\Auth\Application\Contracts\PermissionServiceInterface;
use Modules\Auth\Application\Contracts\RoleServiceInterface;
use Modules\Auth\Application\Services\AuthorizationService;
use Modules\Auth\Application\Services\AuthService;
use Modules\Auth\Application\Services\PermissionService;
use Modules\Auth\Application\Services\RoleService;
use Modules\Auth\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(UserRepositoryInterface::class, function ($app) {
            return new EloquentUserRepository($app->make(UserModel::class));
        });

        $this->app->bind(RoleRepositoryInterface::class, function ($app) {
            return new EloquentRoleRepository($app->make(RoleModel::class));
        });

        $this->app->bind(PermissionRepositoryInterface::class, function ($app) {
            return new EloquentPermissionRepository($app->make(PermissionModel::class));
        });

        // Application Services
        $this->app->singleton(AuthServiceInterface::class, function ($app) {
            return new AuthService(
                $app->make(UserRepositoryInterface::class),
            );
        });

        $this->app->singleton(AuthorizationServiceInterface::class, function ($app) {
            return new AuthorizationService(
                $app->make(UserRepositoryInterface::class),
                $app->make(RoleRepositoryInterface::class),
            );
        });

        $this->app->bind(RoleServiceInterface::class, function ($app) {
            return new RoleService(
                $app->make(RoleRepositoryInterface::class),
            );
        });

        $this->app->bind(PermissionServiceInterface::class, function ($app) {
            return new PermissionService(
                $app->make(PermissionRepositoryInterface::class),
            );
        });

        $this->mergeConfigFrom(__DIR__.'/../../config/auth_module.php', 'auth_module');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishes([
            __DIR__.'/../../config/auth_module.php' => config_path('auth_module.php'),
        ], 'auth-module-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'auth-module-migrations');
    }
}
