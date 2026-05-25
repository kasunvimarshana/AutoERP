<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class InventoryRoutesTest extends TestCase
{
    public function testInventoryRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('inventory.batches.index'));
        self::assertTrue(Route::has('inventory.batches.store'));
        self::assertTrue(Route::has('inventory.batches.show'));
        self::assertTrue(Route::has('inventory.batches.update'));
        self::assertTrue(Route::has('inventory.batches.destroy'));
        self::assertTrue(Route::has('inventory.serials.index'));
        self::assertTrue(Route::has('inventory.serials.store'));
        self::assertTrue(Route::has('inventory.serials.show'));
        self::assertTrue(Route::has('inventory.serials.update'));
        self::assertTrue(Route::has('inventory.serials.destroy'));
        self::assertTrue(Route::has('inventory.valuation-configs.index'));
        self::assertTrue(Route::has('inventory.valuation-configs.store'));
        self::assertTrue(Route::has('inventory.valuation-configs.show'));
        self::assertTrue(Route::has('inventory.valuation-configs.update'));
        self::assertTrue(Route::has('inventory.valuation-configs.destroy'));
        self::assertTrue(Route::has('inventory.stock-levels.index'));
        self::assertTrue(Route::has('inventory.stock-levels.store'));
        self::assertTrue(Route::has('inventory.stock-levels.show'));
        self::assertTrue(Route::has('inventory.stock-levels.update'));
        self::assertTrue(Route::has('inventory.stock-levels.destroy'));
        self::assertTrue(Route::has('inventory.stock-movements.index'));
        self::assertTrue(Route::has('inventory.stock-movements.store'));
        self::assertTrue(Route::has('inventory.stock-movements.show'));
        self::assertTrue(Route::has('inventory.stock-movements.update'));
        self::assertTrue(Route::has('inventory.stock-movements.destroy'));
        self::assertTrue(Route::has('inventory.inventory-cost-layers.index'));
        self::assertTrue(Route::has('inventory.inventory-cost-layers.store'));
        self::assertTrue(Route::has('inventory.inventory-cost-layers.show'));
        self::assertTrue(Route::has('inventory.inventory-cost-layers.update'));
        self::assertTrue(Route::has('inventory.inventory-cost-layers.destroy'));
        self::assertTrue(Route::has('inventory.stock-reservations.index'));
        self::assertTrue(Route::has('inventory.stock-reservations.store'));
        self::assertTrue(Route::has('inventory.stock-reservations.show'));
        self::assertTrue(Route::has('inventory.stock-reservations.update'));
        self::assertTrue(Route::has('inventory.stock-reservations.destroy'));
        self::assertTrue(Route::has('inventory.stock-transfers.index'));
        self::assertTrue(Route::has('inventory.stock-transfers.store'));
        self::assertTrue(Route::has('inventory.stock-transfers.show'));
        self::assertTrue(Route::has('inventory.stock-transfers.update'));
        self::assertTrue(Route::has('inventory.stock-transfers.destroy'));
        self::assertTrue(Route::has('inventory.stock-transfer-lines.index'));
        self::assertTrue(Route::has('inventory.stock-transfer-lines.store'));
        self::assertTrue(Route::has('inventory.stock-transfer-lines.show'));
        self::assertTrue(Route::has('inventory.stock-transfer-lines.update'));
        self::assertTrue(Route::has('inventory.stock-transfer-lines.destroy'));
        self::assertTrue(Route::has('inventory.stock-adjustments.index'));
        self::assertTrue(Route::has('inventory.stock-adjustments.store'));
        self::assertTrue(Route::has('inventory.stock-adjustments.show'));
        self::assertTrue(Route::has('inventory.stock-adjustments.update'));
        self::assertTrue(Route::has('inventory.stock-adjustments.destroy'));
        self::assertTrue(Route::has('inventory.stock-adjustment-lines.index'));
        self::assertTrue(Route::has('inventory.stock-adjustment-lines.store'));
        self::assertTrue(Route::has('inventory.stock-adjustment-lines.show'));
        self::assertTrue(Route::has('inventory.stock-adjustment-lines.update'));
        self::assertTrue(Route::has('inventory.stock-adjustment-lines.destroy'));
        self::assertTrue(Route::has('inventory.cycle-count-headers.index'));
        self::assertTrue(Route::has('inventory.cycle-count-headers.store'));
        self::assertTrue(Route::has('inventory.cycle-count-headers.show'));
        self::assertTrue(Route::has('inventory.cycle-count-headers.update'));
        self::assertTrue(Route::has('inventory.cycle-count-headers.destroy'));
        self::assertTrue(Route::has('inventory.cycle-count-lines.index'));
        self::assertTrue(Route::has('inventory.cycle-count-lines.store'));
        self::assertTrue(Route::has('inventory.cycle-count-lines.show'));
        self::assertTrue(Route::has('inventory.cycle-count-lines.update'));
        self::assertTrue(Route::has('inventory.cycle-count-lines.destroy'));
        self::assertTrue(Route::has('inventory.transfer-orders.index'));
        self::assertTrue(Route::has('inventory.transfer-orders.store'));
        self::assertTrue(Route::has('inventory.transfer-orders.show'));
        self::assertTrue(Route::has('inventory.transfer-orders.update'));
        self::assertTrue(Route::has('inventory.transfer-orders.destroy'));
        self::assertTrue(Route::has('inventory.transfer-order-lines.index'));
        self::assertTrue(Route::has('inventory.transfer-order-lines.store'));
        self::assertTrue(Route::has('inventory.transfer-order-lines.show'));
        self::assertTrue(Route::has('inventory.transfer-order-lines.update'));
        self::assertTrue(Route::has('inventory.transfer-order-lines.destroy'));
        self::assertTrue(Route::has('inventory.trace-logs.index'));
        self::assertTrue(Route::has('inventory.trace-logs.store'));
        self::assertTrue(Route::has('inventory.trace-logs.show'));
        self::assertTrue(Route::has('inventory.trace-logs.update'));
        self::assertTrue(Route::has('inventory.trace-logs.destroy'));
        self::assertTrue(Route::has('inventory.receipt-inspections.index'));
        self::assertTrue(Route::has('inventory.receipt-inspections.store'));
        self::assertTrue(Route::has('inventory.receipt-inspections.show'));
        self::assertTrue(Route::has('inventory.receipt-inspections.update'));
        self::assertTrue(Route::has('inventory.receipt-inspections.destroy'));
        self::assertTrue(Route::has('inventory.put-away-tasks.index'));
        self::assertTrue(Route::has('inventory.put-away-tasks.store'));
        self::assertTrue(Route::has('inventory.put-away-tasks.show'));
        self::assertTrue(Route::has('inventory.put-away-tasks.update'));
        self::assertTrue(Route::has('inventory.put-away-tasks.destroy'));
        self::assertTrue(Route::has('inventory.picking-tasks.index'));
        self::assertTrue(Route::has('inventory.picking-tasks.store'));
        self::assertTrue(Route::has('inventory.picking-tasks.show'));
        self::assertTrue(Route::has('inventory.picking-tasks.update'));
        self::assertTrue(Route::has('inventory.picking-tasks.destroy'));
    }

    public function testInventoryRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('inventory.batches.index');

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