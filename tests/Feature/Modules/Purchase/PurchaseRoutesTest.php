<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PurchaseRoutesTest extends TestCase
{
    public function testPurchaseRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('purchase.purchase-orders.index'));
        self::assertTrue(Route::has('purchase.purchase-orders.store'));
        self::assertTrue(Route::has('purchase.purchase-orders.show'));
        self::assertTrue(Route::has('purchase.purchase-orders.update'));
        self::assertTrue(Route::has('purchase.purchase-orders.destroy'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.index'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.store'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.show'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.destroy'));
        self::assertTrue(Route::has('purchase.grn-headers.index'));
        self::assertTrue(Route::has('purchase.grn-headers.store'));
        self::assertTrue(Route::has('purchase.grn-headers.show'));
        self::assertTrue(Route::has('purchase.grn-headers.update'));
        self::assertTrue(Route::has('purchase.grn-headers.destroy'));
        self::assertTrue(Route::has('purchase.grn-lines.index'));
        self::assertTrue(Route::has('purchase.grn-lines.store'));
        self::assertTrue(Route::has('purchase.grn-lines.show'));
        self::assertTrue(Route::has('purchase.grn-lines.update'));
        self::assertTrue(Route::has('purchase.grn-lines.destroy'));
        self::assertTrue(Route::has('purchase.purchase-returns.index'));
        self::assertTrue(Route::has('purchase.purchase-returns.store'));
        self::assertTrue(Route::has('purchase.purchase-returns.show'));
        self::assertTrue(Route::has('purchase.purchase-returns.update'));
        self::assertTrue(Route::has('purchase.purchase-returns.destroy'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.index'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.store'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.show'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.destroy'));
        self::assertTrue(Route::has('purchase.workflows.transition'));
        self::assertTrue(Route::has('purchase.workflows.document'));
        self::assertTrue(Route::has('purchase.workflows.payment.allocate'));
        self::assertTrue(Route::has('purchase.workflows.inventory.post'));
        self::assertTrue(Route::has('purchase.workflows.finance.post'));
        self::assertTrue(Route::has('purchase.workflows.finance.reverse'));
    }

    public function testPurchaseRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('purchase.purchase-orders.index');

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
