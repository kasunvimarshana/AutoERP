<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Inventory\Constants\InventoryPermission;
use Tests\TestCase;

final class InventoryPermissionBoundaryTest extends TestCase
{
    public function test_inventory_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (InventoryPermission::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('inventory', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_inventory_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.inventory.stock-balances.index' => InventoryPermission::STOCK_VIEW,
            'api.v1.inventory.availability' => InventoryPermission::STOCK_VIEW,
            'api.v1.inventory.state-changes.index' => InventoryPermission::AUDIT_VIEW,
            'api.v1.inventory.reservations.index' => InventoryPermission::RESERVATIONS_VIEW,
            'api.v1.inventory.reservations.store' => InventoryPermission::RESERVATIONS_MANAGE,
            'api.v1.inventory.reservations.release' => InventoryPermission::RESERVATIONS_MANAGE,
            'api.v1.inventory.allocations.index' => InventoryPermission::ALLOCATIONS_VIEW,
            'api.v1.inventory.allocations.store' => InventoryPermission::ALLOCATIONS_MANAGE,
            'api.v1.inventory.allocations.issue' => InventoryPermission::ALLOCATIONS_ISSUE,
            'api.v1.inventory.allocations.release' => InventoryPermission::ALLOCATIONS_MANAGE,
            'api.v1.inventory.adjustments.index' => InventoryPermission::ADJUSTMENTS_VIEW,
            'api.v1.inventory.adjustments.store' => InventoryPermission::ADJUSTMENTS_MANAGE,
            'api.v1.inventory.adjustments.post' => InventoryPermission::ADJUSTMENTS_POST,
            'api.v1.inventory.transfers.index' => InventoryPermission::TRANSFERS_VIEW,
            'api.v1.inventory.transfers.store' => InventoryPermission::TRANSFERS_MANAGE,
            'api.v1.inventory.transfers.post' => InventoryPermission::TRANSFERS_DISPATCH,
            'api.v1.inventory.transfers.receive' => InventoryPermission::TRANSFERS_RECEIVE,
            'api.v1.inventory.transfers.cancel' => InventoryPermission::TRANSFERS_MANAGE,
            'api.v1.inventory.valuation-layers.index' => InventoryPermission::VALUATION_VIEW,
            'api.v1.inventory.cost-adjustments.index' => InventoryPermission::COST_ADJUSTMENTS_VIEW,
            'api.v1.inventory.cost-adjustments.store' => InventoryPermission::COST_ADJUSTMENTS_MANAGE,
            'api.v1.inventory.cost-adjustments.post' => InventoryPermission::COST_ADJUSTMENTS_POST,
            'api.v1.inventory.stock-counts.index' => InventoryPermission::STOCK_COUNTS_VIEW,
            'api.v1.inventory.stock-counts.store' => InventoryPermission::STOCK_COUNTS_MANAGE,
            'api.v1.inventory.stock-counts.approve' => InventoryPermission::STOCK_COUNTS_APPROVE,
            'api.v1.inventory.stock-counts.post' => InventoryPermission::STOCK_COUNTS_POST,
            'api.v1.inventory.batches.index' => InventoryPermission::TRACKING_VIEW,
            'api.v1.inventory.serials.index' => InventoryPermission::TRACKING_VIEW,
        ];

        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$permission,
                $route->gatherMiddleware(),
                "Route [{$routeName}] must require [{$permission}].",
            );
        }
    }
}
