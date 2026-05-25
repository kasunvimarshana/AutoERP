<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\User;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class UserRoutesTest extends TestCase
{
    public function test_user_routes_are_registered(): void
    {
        self::assertTrue(Route::has('user.users.index'));
        self::assertTrue(Route::has('user.roles.index'));
        self::assertTrue(Route::has('user.permissions.index'));
        self::assertTrue(Route::has('user.role-permissions.index'));
        self::assertTrue(Route::has('user.user-roles.index'));
        self::assertTrue(Route::has('user.user-permissions.index'));
        self::assertTrue(Route::has('user.user-tenants.index'));
        self::assertTrue(Route::has('user.user-documents.index'));
        self::assertTrue(Route::has('user.user-devices.index'));
    }

    public function test_user_routes_use_current_user_and_current_tenant_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('user.users.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
    }
}
