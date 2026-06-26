<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Modules\Auth\Services\AccessTokenRouter;
use Modules\Auth\Services\PlatformAuthenticationService;
use Modules\Auth\Services\PlatformAuthProfileBuilder;
use Modules\Auth\Services\TenantAuthenticationService;
use Modules\Auth\Services\TenantAuthProfileBuilder;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionDirectoryInterface;
use Modules\Core\Http\Middleware\CurrentTenantMiddleware;
use Modules\Core\Http\Middleware\RequireCurrentTenantAccessMiddleware;
use Modules\Core\Http\Middleware\ResolveCurrentTenantMiddleware;
use Modules\User\Contracts\AuthenticationPrincipalProviderInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;
use Tests\TestCase;

final class AuthContainerBindingsTest extends TestCase
{
    public function test_critical_auth_contracts_and_login_graphs_resolve(): void
    {
        self::assertInstanceOf(
            PlatformPermissionCheckerInterface::class,
            $this->app->make(PlatformPermissionCheckerInterface::class),
        );
        self::assertInstanceOf(
            PlatformPermissionDirectoryInterface::class,
            $this->app->make(PlatformPermissionDirectoryInterface::class),
        );
        self::assertInstanceOf(
            TenantUserAuthenticationDirectoryInterface::class,
            $this->app->make(TenantUserAuthenticationDirectoryInterface::class),
        );
        self::assertInstanceOf(
            PlatformOperatorAuthenticationDirectoryInterface::class,
            $this->app->make(PlatformOperatorAuthenticationDirectoryInterface::class),
        );
        self::assertInstanceOf(
            AuthenticationPrincipalProviderInterface::class,
            $this->app->make(AuthenticationPrincipalProviderInterface::class),
        );

        self::assertInstanceOf(TenantAuthenticationService::class, $this->app->make(TenantAuthenticationService::class));
        self::assertInstanceOf(PlatformAuthenticationService::class, $this->app->make(PlatformAuthenticationService::class));
        self::assertInstanceOf(TenantAuthProfileBuilder::class, $this->app->make(TenantAuthProfileBuilder::class));
        self::assertInstanceOf(PlatformAuthProfileBuilder::class, $this->app->make(PlatformAuthProfileBuilder::class));
        self::assertInstanceOf(AccessTokenRouter::class, $this->app->make(AccessTokenRouter::class));
        self::assertInstanceOf(
            ResolveCurrentTenantMiddleware::class,
            $this->app->make(ResolveCurrentTenantMiddleware::class),
        );
        self::assertInstanceOf(
            RequireCurrentTenantAccessMiddleware::class,
            $this->app->make(RequireCurrentTenantAccessMiddleware::class),
        );
        self::assertInstanceOf(CurrentTenantMiddleware::class, $this->app->make(CurrentTenantMiddleware::class));
    }
}
