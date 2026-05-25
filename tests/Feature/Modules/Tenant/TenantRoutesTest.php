<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Tenant;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class TenantRoutesTest extends TestCase
{
    public function test_tenant_routes_are_registered(): void
    {
        self::assertTrue(Route::has('tenant.tenants.index'));
        self::assertTrue(Route::has('tenant.plans.index'));
        self::assertTrue(Route::has('tenant.setting-groups.index'));
        self::assertTrue(Route::has('tenant.settings.index'));
        self::assertTrue(Route::has('tenant.documents.index'));
        self::assertTrue(Route::has('tenant.domains.index'));
        self::assertTrue(Route::has('tenant.tenants.activate'));
        self::assertTrue(Route::has('tenant.tenants.suspend'));
        self::assertTrue(Route::has('tenant.tenants.deactivate'));
    }

    public function test_tenant_routes_use_current_user_and_current_tenant_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('tenant.tenants.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
    }
}
