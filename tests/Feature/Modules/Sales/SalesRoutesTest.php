<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SalesRoutesTest extends TestCase
{
    public function testSalesRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('sales.sales-orders.index'));
        self::assertTrue(Route::has('sales.sales-orders.store'));
        self::assertTrue(Route::has('sales.sales-orders.show'));
        self::assertTrue(Route::has('sales.sales-orders.update'));
        self::assertTrue(Route::has('sales.sales-orders.destroy'));
        self::assertTrue(Route::has('sales.sales-order-lines.index'));
        self::assertTrue(Route::has('sales.sales-order-lines.store'));
        self::assertTrue(Route::has('sales.sales-order-lines.show'));
        self::assertTrue(Route::has('sales.sales-order-lines.update'));
        self::assertTrue(Route::has('sales.sales-order-lines.destroy'));
        self::assertTrue(Route::has('sales.gdn-headers.index'));
        self::assertTrue(Route::has('sales.gdn-headers.store'));
        self::assertTrue(Route::has('sales.gdn-headers.show'));
        self::assertTrue(Route::has('sales.gdn-headers.update'));
        self::assertTrue(Route::has('sales.gdn-headers.destroy'));
        self::assertTrue(Route::has('sales.gdn-lines.index'));
        self::assertTrue(Route::has('sales.gdn-lines.store'));
        self::assertTrue(Route::has('sales.gdn-lines.show'));
        self::assertTrue(Route::has('sales.gdn-lines.update'));
        self::assertTrue(Route::has('sales.gdn-lines.destroy'));
        self::assertTrue(Route::has('sales.sales-returns.index'));
        self::assertTrue(Route::has('sales.sales-returns.store'));
        self::assertTrue(Route::has('sales.sales-returns.show'));
        self::assertTrue(Route::has('sales.sales-returns.update'));
        self::assertTrue(Route::has('sales.sales-returns.destroy'));
        self::assertTrue(Route::has('sales.sales-return-lines.index'));
        self::assertTrue(Route::has('sales.sales-return-lines.store'));
        self::assertTrue(Route::has('sales.sales-return-lines.show'));
        self::assertTrue(Route::has('sales.sales-return-lines.update'));
        self::assertTrue(Route::has('sales.sales-return-lines.destroy'));
    }

    public function testSalesRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('sales.sales-orders.index');

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