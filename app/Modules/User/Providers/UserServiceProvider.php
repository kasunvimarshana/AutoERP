<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\User\Console\Commands\SyncPlatformPermissionCatalogCommand;
use Modules\User\Contracts\AuthenticatedUserProviderInterface;
use Modules\User\Contracts\AuthenticationPrincipalProviderInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\User\Http\Middleware\RequirePlatformPermissionMiddleware;
use Modules\User\Http\Middleware\UserContextResolutionMiddleware;
use Modules\User\Models\UserModel;
use Modules\User\Policies\UserPolicy;
use Modules\User\Repositories\EloquentUserRepository;
use Modules\User\Repositories\EloquentUserRoleRepository;
use Modules\User\Repositories\EloquentUserOrganizationUnitRepository;
use Modules\User\Repositories\UserRepositoryInterface;
use Modules\User\Repositories\UserRoleRepositoryInterface;
use Modules\User\Repositories\UserOrganizationUnitRepositoryInterface;
use Modules\User\Services\AuthenticatedUserProvider;
use Modules\User\Services\Authentication\AuthenticationPrincipalProvider;
use Modules\User\Services\Authentication\PlatformOperatorAuthenticationDirectory;
use Modules\User\Services\Authentication\TenantUserAuthenticationDirectory;
use Modules\User\Services\PermissionDefinitionRegistry;
use Modules\User\Services\PlatformOperatorAccessResolver;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationTokenCodec;
use Modules\User\Services\Provisioning\TenantAccessProvisioner;
use Modules\User\Services\TenantUserRegistrationService;
use Modules\User\Contracts\TenantUserRegistrationInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionDirectoryInterface;
use Modules\Core\Contracts\TenantUserAccessCheckerInterface;
use Modules\User\Services\UserAccessResolver;
use Modules\User\Services\UserPermissionChecker;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\User\Constants\UserPermission;
use Modules\User\Services\TenantLimits\UserLimitUsageContributor;
use Modules\User\Services\OrganizationUnit\UserOrganizationUnitLifecycleBlocker;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/user.php', 'user');
        $this->app->tag([UserLimitUsageContributor::class], 'tenant.limit_usage');
        $this->app->tag([UserOrganizationUnitLifecycleBlocker::class], 'organization-unit.lifecycle_blocker');

        $this->app->singleton(AuthenticatedUserProviderInterface::class, AuthenticatedUserProvider::class);
        $this->app->singleton(PlatformOperatorInvitationTokenCodec::class, fn (): PlatformOperatorInvitationTokenCodec => new PlatformOperatorInvitationTokenCodec((string) config('app.key')));
        $this->app->scoped(AuthenticationPrincipalProviderInterface::class, AuthenticationPrincipalProvider::class);
        $this->app->scoped(TenantUserAuthenticationDirectoryInterface::class, TenantUserAuthenticationDirectory::class);
        $this->app->scoped(PlatformOperatorAuthenticationDirectoryInterface::class, PlatformOperatorAuthenticationDirectory::class);
        $this->app->singleton(PermissionDefinitionRegistryInterface::class, PermissionDefinitionRegistry::class);
        $this->app->singleton(TenantAccessProvisionerInterface::class, TenantAccessProvisioner::class);
        $this->app->scoped(UserAccessResolver::class);
        $this->app->scoped(PlatformOperatorAccessResolver::class);
        $this->app->scoped(PlatformOperatorCheckerInterface::class, PlatformOperatorAccessResolver::class);
        $this->app->scoped(PlatformPermissionCheckerInterface::class, PlatformOperatorAccessResolver::class);
        $this->app->scoped(PlatformPermissionDirectoryInterface::class, PlatformOperatorAccessResolver::class);
        $this->app->scoped(TenantUserAccessCheckerInterface::class, UserAccessResolver::class);
        $this->app->scoped(OrganizationUnitUserAccessCheckerInterface::class, UserAccessResolver::class);
        $this->app->scoped(PermissionCheckerInterface::class, UserPermissionChecker::class);

        $this->app->scoped(TenantUserRegistrationInterface::class, TenantUserRegistrationService::class);
        $this->app->scoped(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->scoped(UserRoleRepositoryInterface::class, EloquentUserRoleRepository::class);
        $this->app->scoped(
            UserOrganizationUnitRepositoryInterface::class,
            EloquentUserOrganizationUnitRepository::class,
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
                SyncPlatformPermissionCatalogCommand::class,
            ]);
        }
    }
}
