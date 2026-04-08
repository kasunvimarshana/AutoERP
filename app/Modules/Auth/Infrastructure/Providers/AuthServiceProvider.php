<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Application\Contracts\RoleServiceInterface;
use Modules\Auth\Application\Contracts\UserServiceInterface;
use Modules\Auth\Application\Services\AuthorizationService;
use Modules\Auth\Application\Services\RoleService;
use Modules\Auth\Application\Services\UserService;
use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(AuthorizationServiceInterface::class, AuthorizationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../../routes/api.php');
    }
}
