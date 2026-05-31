<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Vehicle;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VehicleRoutesTest extends TestCase
{
    public function testVehicleRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('vehicle.vehicles.index'));
        self::assertTrue(Route::has('vehicle.vehicles.store'));
        self::assertTrue(Route::has('vehicle.vehicles.show'));
        self::assertTrue(Route::has('vehicle.vehicles.update'));
        self::assertTrue(Route::has('vehicle.vehicles.destroy'));
        self::assertTrue(Route::has('vehicle.vehicles.lookup'));
        self::assertTrue(Route::has('vehicle.vehicles.validate.usage'));
        self::assertTrue(Route::has('vehicle.vehicles.ownerships.index'));
        self::assertTrue(Route::has('vehicle.vehicles.ownerships.store'));
        self::assertTrue(Route::has('vehicle.vehicles.ownerships.update'));
        self::assertTrue(Route::has('vehicle.vehicles.ownerships.current'));
        self::assertTrue(Route::has('vehicle.vehicles.ownerships.end'));
        self::assertTrue(Route::has('vehicle.vehicles.ownerships.set-current'));

        self::assertTrue(Route::has('vehicle.vehicle-documents.index'));
        self::assertTrue(Route::has('vehicle.vehicle-documents.store'));
        self::assertTrue(Route::has('vehicle.vehicle-documents.show'));
        self::assertTrue(Route::has('vehicle.vehicle-documents.update'));
        self::assertTrue(Route::has('vehicle.vehicle-documents.destroy'));
    }

    public function testVehicleRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('vehicle.vehicles.index');

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
