<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\UOM;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class UomRoutesTest extends TestCase
{
    public function testUomRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('uom.units-of-measure.index'));
        self::assertTrue(Route::has('uom.units-of-measure.store'));
        self::assertTrue(Route::has('uom.units-of-measure.show'));
        self::assertTrue(Route::has('uom.units-of-measure.update'));
        self::assertTrue(Route::has('uom.units-of-measure.destroy'));
        self::assertTrue(Route::has('uom.units-of-measure.activate'));
        self::assertTrue(Route::has('uom.units-of-measure.deactivate'));
        self::assertTrue(Route::has('uom.units-of-measure.usage'));
        self::assertTrue(Route::has('uom.categories.index'));
        self::assertTrue(Route::has('uom.uom-conversions.index'));
        self::assertTrue(Route::has('uom.uom-conversions.store'));
        self::assertTrue(Route::has('uom.uom-conversions.show'));
        self::assertTrue(Route::has('uom.uom-conversions.update'));
        self::assertTrue(Route::has('uom.uom-conversions.destroy'));
        self::assertTrue(Route::has('uom.uom-conversions.activate'));
        self::assertTrue(Route::has('uom.uom-conversions.deactivate'));
    }

    public function testUomRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('uom.units-of-measure.index');

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
