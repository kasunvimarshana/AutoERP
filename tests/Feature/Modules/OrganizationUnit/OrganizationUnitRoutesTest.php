<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OrganizationUnit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class OrganizationUnitRoutesTest extends TestCase
{
    public function test_organization_unit_routes_are_registered(): void
    {
        self::assertTrue(Route::has('organization-unit.organization-unit-types.index'));
        self::assertTrue(Route::has('organization-unit.organization-units.index'));
        self::assertTrue(Route::has('organization-unit.organization-unit-setting-groups.index'));
        self::assertTrue(Route::has('organization-unit.organization-unit-settings.index'));
        self::assertTrue(Route::has('organization-unit.organization-unit-documents.index'));
    }

    public function test_organization_unit_routes_use_current_user_and_current_tenant_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('organization-unit.organization-units.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
    }
}
