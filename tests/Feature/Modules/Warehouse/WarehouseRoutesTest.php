<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Warehouse;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class WarehouseRoutesTest extends TestCase
{
    public function testWarehouseRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('warehouse.warehouses.index'));
        self::assertTrue(Route::has('warehouse.warehouses.store'));
        self::assertTrue(Route::has('warehouse.warehouses.show'));
        self::assertTrue(Route::has('warehouse.warehouses.update'));
        self::assertTrue(Route::has('warehouse.warehouses.destroy'));
        self::assertTrue(Route::has('warehouse.warehouse-locations.index'));
        self::assertTrue(Route::has('warehouse.warehouse-locations.store'));
        self::assertTrue(Route::has('warehouse.warehouse-locations.show'));
        self::assertTrue(Route::has('warehouse.warehouse-locations.update'));
        self::assertTrue(Route::has('warehouse.warehouse-locations.destroy'));
    }

    public function testWarehouseRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('warehouse.warehouses.index');

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