<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleRental;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VehicleRentalRoutesTest extends TestCase
{
    public function testVehicleRentalRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreements.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreements.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreements.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreements.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreements.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreements.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreements.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreements.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreements.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreements.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-running-charts.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-running-charts.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-running-charts.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-running-charts.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-running-charts.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-running-charts.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-running-charts.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-running-charts.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-running-charts.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-running-charts.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-credit-notes.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-credit-notes.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-credit-notes.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-credit-notes.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-credit-notes.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-debit-notes.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-debit-notes.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-debit-notes.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-debit-notes.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessor-agreement-debit-notes.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-credit-notes.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-credit-notes.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-credit-notes.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-credit-notes.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-credit-notes.destroy'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-debit-notes.index'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-debit-notes.store'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-debit-notes.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-debit-notes.update'));
        self::assertTrue(Route::has('vehiclerental.vehicle-rental-lessee-agreement-debit-notes.destroy'));
    }

    public function testVehicleRentalRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('vehiclerental.vehicle-rental-lessor-agreements.index');

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