<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\SystemUser;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SystemUserRoutesTest extends TestCase
{
    public function testSystemUserRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('system-user.system-users.index'));
        self::assertTrue(Route::has('system-user.system-users.store'));
        self::assertTrue(Route::has('system-user.system-users.show'));
        self::assertTrue(Route::has('system-user.system-users.update'));
        self::assertTrue(Route::has('system-user.system-users.destroy'));
    }

    public function testSystemUserRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('system-user.system-users.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
        self::assertContains(
            (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
            $middlewares,
        );
    }
}
