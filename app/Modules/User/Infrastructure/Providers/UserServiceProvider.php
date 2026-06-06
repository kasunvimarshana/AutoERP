<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\User\Application\Contracts\AuthenticatedUserProviderInterface;
use Modules\User\Application\Repositories\PermissionRepositoryInterface;
use Modules\User\Application\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Application\Repositories\RoleRepositoryInterface;
use Modules\User\Application\Repositories\UserDeviceRepositoryInterface;
use Modules\User\Application\Repositories\UserDocumentRepositoryInterface;
use Modules\User\Application\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\Repositories\UserRoleRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Modules\User\Domain\Services\UserDomainService;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RolePermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDocumentModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserPermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserTenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentRolePermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserDeviceRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserDocumentRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserPermissionRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRoleRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserTenantRepository;
use Modules\User\Infrastructure\Services\AuthenticatedUserProvider;
use Modules\User\Presentation\Console\Commands\UserCreateCommand;
use Modules\User\Presentation\Http\Middleware\UserContextResolutionMiddleware;
use Modules\User\Presentation\Policies\UserPolicy;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/user.php', 'user');

        $this->app->singleton(AuthenticatedUserProviderInterface::class, AuthenticatedUserProvider::class);

        $this->app->singleton(UserDomainServiceInterface::class, UserDomainService::class);
        $this->app->singleton(
            UserRepositoryInterface::class,
            fn (): UserRepositoryInterface => new EloquentUserRepository(new UserModel),
        );
        $this->app->singleton(
            RoleRepositoryInterface::class,
            fn (): RoleRepositoryInterface => new EloquentRoleRepository(new RoleModel),
        );
        $this->app->singleton(
            PermissionRepositoryInterface::class,
            fn (): PermissionRepositoryInterface => new EloquentPermissionRepository(new PermissionModel),
        );
        $this->app->singleton(
            RolePermissionRepositoryInterface::class,
            fn (): RolePermissionRepositoryInterface => new EloquentRolePermissionRepository(new RolePermissionModel),
        );
        $this->app->singleton(
            UserRoleRepositoryInterface::class,
            fn (): UserRoleRepositoryInterface => new EloquentUserRoleRepository(new UserRoleModel),
        );
        $this->app->singleton(
            UserPermissionRepositoryInterface::class,
            fn (): UserPermissionRepositoryInterface => new EloquentUserPermissionRepository(new UserPermissionModel),
        );
        $this->app->singleton(
            UserTenantRepositoryInterface::class,
            fn (): UserTenantRepositoryInterface => new EloquentUserTenantRepository(new UserTenantModel),
        );
        $this->app->singleton(
            UserDocumentRepositoryInterface::class,
            fn (): UserDocumentRepositoryInterface => new EloquentUserDocumentRepository(new UserDocumentModel),
        );
        $this->app->singleton(
            UserDeviceRepositoryInterface::class,
            fn (): UserDeviceRepositoryInterface => new EloquentUserDeviceRepository(new UserDeviceModel),
        );
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware(
            (string) config('user.context.middleware_alias', 'current.user-record'),
            UserContextResolutionMiddleware::class,
        );

        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');

        Gate::policy(UserModel::class, UserPolicy::class);
        if ($this->app->runningInConsole()) {
            $this->commands([
                UserCreateCommand::class,
            ]);
        }
    }
}
