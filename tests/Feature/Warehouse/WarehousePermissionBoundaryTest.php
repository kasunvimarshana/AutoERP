<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Warehouse\Services\WarehouseAuthorizationService;
use Tests\TestCase;

final class WarehousePermissionBoundaryTest extends TestCase
{
    public function test_warehouse_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (WarehouseAuthorizationService::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('warehouse', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_warehouse_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.warehouses.default' => WarehouseAuthorizationService::WAREHOUSES_VIEW,
            'api.v1.warehouses.index' => WarehouseAuthorizationService::WAREHOUSES_VIEW,
            'api.v1.warehouses.show' => WarehouseAuthorizationService::WAREHOUSES_VIEW,
            'api.v1.warehouses.store' => WarehouseAuthorizationService::WAREHOUSES_CREATE,
            'api.v1.warehouses.update' => WarehouseAuthorizationService::WAREHOUSES_UPDATE,
            'api.v1.warehouses.activate' => WarehouseAuthorizationService::WAREHOUSES_ACTIVATE,
            'api.v1.warehouses.deactivate' => WarehouseAuthorizationService::WAREHOUSES_DEACTIVATE,
            'api.v1.warehouses.destroy' => WarehouseAuthorizationService::WAREHOUSES_DELETE,
            'api.v1.warehouse-locations.default' => WarehouseAuthorizationService::LOCATIONS_VIEW,
            'api.v1.warehouse-locations.index' => WarehouseAuthorizationService::LOCATIONS_VIEW,
            'api.v1.warehouse-locations.show' => WarehouseAuthorizationService::LOCATIONS_VIEW,
            'api.v1.warehouse-locations.store' => WarehouseAuthorizationService::LOCATIONS_CREATE,
            'api.v1.warehouse-locations.update' => WarehouseAuthorizationService::LOCATIONS_UPDATE,
            'api.v1.warehouse-locations.activate' => WarehouseAuthorizationService::LOCATIONS_ACTIVATE,
            'api.v1.warehouse-locations.deactivate' => WarehouseAuthorizationService::LOCATIONS_DEACTIVATE,
            'api.v1.warehouse-locations.destroy' => WarehouseAuthorizationService::LOCATIONS_DELETE,
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
