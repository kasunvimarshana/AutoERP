<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\User\Console\Commands\UserCreateCommand;
use Modules\User\Contracts\AuthenticatedUserProviderInterface;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\User\Http\Middleware\RequirePlatformPermissionMiddleware;
use Modules\User\Http\Middleware\UserContextResolutionMiddleware;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\RoleModel;
use Modules\User\Models\RolePermissionModel;
use Modules\User\Models\UserDeviceModel;
use Modules\User\Models\UserDocumentModel;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserPermissionModel;
use Modules\User\Models\UserRoleModel;
use Modules\User\Models\UserOrganizationUnitModel;
use Modules\User\Policies\UserPolicy;
use Modules\User\Repositories\EloquentPermissionRepository;
use Modules\User\Repositories\EloquentRolePermissionRepository;
use Modules\User\Repositories\EloquentRoleRepository;
use Modules\User\Repositories\EloquentUserDeviceRepository;
use Modules\User\Repositories\EloquentUserDocumentRepository;
use Modules\User\Repositories\EloquentUserPermissionRepository;
use Modules\User\Repositories\EloquentUserRepository;
use Modules\User\Repositories\EloquentUserRoleRepository;
use Modules\User\Repositories\EloquentUserOrganizationUnitRepository;
use Modules\User\Repositories\PermissionRepositoryInterface;
use Modules\User\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Repositories\RoleRepositoryInterface;
use Modules\User\Repositories\UserDeviceRepositoryInterface;
use Modules\User\Repositories\UserDocumentRepositoryInterface;
use Modules\User\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Repositories\UserRepositoryInterface;
use Modules\User\Repositories\UserRoleRepositoryInterface;
use Modules\User\Repositories\UserOrganizationUnitRepositoryInterface;
use Modules\User\Services\AuthenticatedUserProvider;
use Modules\User\Services\PermissionDefinitionRegistry;
use Modules\User\Services\PlatformPermissionChecker;
use Modules\User\Services\Provisioning\TenantAccessProvisioner;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Modules\User\Services\Rules\UserDomainService;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\TenantUserAccessCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Services\UserAccessResolver;
use Modules\User\Services\UserPermissionChecker;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\User\Constants\UserPermission;
use Modules\User\Services\TenantLimits\UserLimitUsageContributor;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/user.php', 'user');
        $this->app->tag([UserLimitUsageContributor::class], 'tenant.limit_usage');

        $this->app->singleton(AuthenticatedUserProviderInterface::class, AuthenticatedUserProvider::class);
        $this->app->singleton(PermissionDefinitionRegistryInterface::class, PermissionDefinitionRegistry::class);
        $this->app->singleton(TenantAccessProvisionerInterface::class, TenantAccessProvisioner::class);
        $this->app->scoped(UserAccessResolver::class);
        $this->app->scoped(PlatformOperatorCheckerInterface::class, UserAccessResolver::class);
        $this->app->scoped(PlatformPermissionCheckerInterface::class, fn (): PlatformPermissionCheckerInterface => new PlatformPermissionChecker(new UserModel, new PlatformOperatorPermissionModel));
        $this->app->scoped(TenantUserAccessCheckerInterface::class, UserAccessResolver::class);
        $this->app->scoped(OrganizationUnitUserAccessCheckerInterface::class, UserAccessResolver::class);
        $this->app->scoped(PermissionCheckerInterface::class, UserPermissionChecker::class);

        $this->app->singleton(UserDomainServiceInterface::class, UserDomainService::class);
        $this->app->scoped(
            UserRepositoryInterface::class,
            fn ($app): UserRepositoryInterface => new EloquentUserRepository(
                new UserModel,
                $app->make(TenantExecutionContextInterface::class),
            ),
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
            UserOrganizationUnitRepositoryInterface::class,
            fn (): UserOrganizationUnitRepositoryInterface => new EloquentUserOrganizationUnitRepository(new UserOrganizationUnitModel),
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
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('user', UserPermission::descriptions());

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware(
            (string) config('user.platform.permission_middleware_alias', 'platform.permission'),
            RequirePlatformPermissionMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('user.context.middleware_alias', 'current.user-record'),
            UserContextResolutionMiddleware::class,
        );

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Gate::policy(UserModel::class, UserPolicy::class);
        if ($this->app->runningInConsole()) {
            $this->commands([
                UserCreateCommand::class,
            ]);
        }
    }
}
