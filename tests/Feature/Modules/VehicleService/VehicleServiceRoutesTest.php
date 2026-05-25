<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleService;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VehicleServiceRoutesTest extends TestCase
{
    public function testVehicleServiceRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('vehicleservice.vehicle-service-types.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-types.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-types.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-types.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-types.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-cards.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-cards.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-cards.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-cards.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-cards.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-card-lines.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-card-lines.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-card-lines.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-card-lines.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-job-card-lines.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-items.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-items.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-items.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-items.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-items.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-non-inventory-items.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-non-inventory-items.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-non-inventory-items.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-non-inventory-items.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-non-inventory-items.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-assignments.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-assignments.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-assignments.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-assignments.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-labor-assignments.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostics.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostics.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostics.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostics.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostics.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostic-lines.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostic-lines.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostic-lines.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostic-lines.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-diagnostic-lines.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspections.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspections.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspections.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspections.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspections.destroy'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspection-lines.index'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspection-lines.store'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspection-lines.show'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspection-lines.update'));
        self::assertTrue(Route::has('vehicleservice.vehicle-service-inspection-lines.destroy'));
    }

    public function testVehicleServiceRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('vehicleservice.vehicle-service-types.index');

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