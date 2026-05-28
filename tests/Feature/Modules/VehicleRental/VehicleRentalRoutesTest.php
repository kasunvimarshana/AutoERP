<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleRental;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VehicleRentalRoutesTest extends TestCase
{
    public function testVehicleRentalRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('vehiclerental.agreements.index'));
        self::assertTrue(Route::has('vehiclerental.agreements.store'));
        self::assertTrue(Route::has('vehiclerental.agreements.show'));
        self::assertTrue(Route::has('vehiclerental.agreements.update'));
        self::assertTrue(Route::has('vehiclerental.running-charts.index'));
        self::assertTrue(Route::has('vehiclerental.running-charts.store'));
        self::assertTrue(Route::has('vehiclerental.running-charts.show'));
        self::assertTrue(Route::has('vehiclerental.running-charts.update'));
        self::assertTrue(Route::has('vehiclerental.agreements.lines.sync'));
        self::assertTrue(Route::has('vehiclerental.agreements.rates.sync'));
        self::assertTrue(Route::has('vehiclerental.agreements.rate-rules.sync'));
        self::assertTrue(Route::has('vehiclerental.agreements.extra-charges.sync'));
        self::assertTrue(Route::has('vehiclerental.agreements.billing-preview'));
        self::assertTrue(Route::has('vehiclerental.running-charts.lines.sync'));
        self::assertTrue(Route::has('vehiclerental.replacements.store'));
        self::assertTrue(Route::has('vehiclerental.replacements.update'));
        self::assertTrue(Route::has('vehiclerental.breakdowns.store'));
        self::assertTrue(Route::has('vehiclerental.breakdowns.update'));
        self::assertTrue(Route::has('vehiclerental.settings.show'));
        self::assertTrue(Route::has('vehiclerental.settings.upsert'));
        self::assertTrue(Route::has('vehiclerental.settings.initialize'));
        self::assertTrue(Route::has('vehiclerental.status-history.show'));
        self::assertTrue(Route::has('vehiclerental.vehicle-availability.show'));
        self::assertTrue(Route::has('vehiclerental.provider-payables.index'));
        self::assertTrue(Route::has('vehiclerental.workflow.agreements.transition'));
        self::assertTrue(Route::has('vehiclerental.workflow.running-charts.transition'));
        self::assertTrue(Route::has('vehiclerental.workflow.invoice'));
        self::assertTrue(Route::has('vehiclerental.workflow.payments.allocate'));
        self::assertTrue(Route::has('vehiclerental.workflow.provider-payables.store'));
        self::assertTrue(Route::has('vehiclerental.workflow.provider-payables.payments.allocate'));
        self::assertTrue(Route::has('vehiclerental.workflow.finance.post'));
        self::assertTrue(Route::has('vehiclerental.workflow.finance.reverse'));
        self::assertTrue(Route::has('vehiclerental.integration.invoice'));
        self::assertTrue(Route::has('vehiclerental.integration.payments.allocate'));
        self::assertTrue(Route::has('vehiclerental.integration.provider-payables.store'));
        self::assertTrue(Route::has('vehiclerental.integration.provider-payables.payments.allocate'));
    }

    public function testVehicleRentalRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('vehiclerental.agreements.index');

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
