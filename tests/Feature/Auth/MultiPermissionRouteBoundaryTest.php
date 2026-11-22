<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Modules\Promotion\Constants\PromotionPermission;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Tests\TestCase;

final class MultiPermissionRouteBoundaryTest extends TestCase
{
    public function test_multi_permission_lookup_routes_have_variadic_middleware(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        $purchaseLookups = [
            'api.v1.purchase.suppliers.purchase-context',
            'api.v1.purchase.items.purchase-context',
            'api.v1.purchase.adjustments.catalogue',
            'api.v1.purchase.warehouses.index',
            'api.v1.purchase.warehouses.locations',
        ];
        $expectedPurchasePerms = implode(',', [
            PurchaseAuthorizationService::ORDERS_VIEW,
            PurchaseAuthorizationService::FAST_PURCHASE_LOOKUPS,
            PurchaseAuthorizationService::FAST_PURCHASE_VIEW,
            PurchaseAuthorizationService::FAST_PURCHASE_EXECUTE,
        ]);

        foreach ($purchaseLookups as $name) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, "Route [{$name}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$expectedPurchasePerms,
                $route->gatherMiddleware(),
                "Route [{$name}] must require purchase lookup permissions.",
            );
        }

        $promotionLookups = [
            'api.v1.promotions.job-items.lookup' => implode(',', [
                PromotionPermission::VIEW,
                PromotionPermission::MANAGE,
                PromotionPermission::REDEEM,
            ]),
            'api.v1.promotions.available' => implode(',', [
                PromotionPermission::VIEW,
                PromotionPermission::MANAGE,
                PromotionPermission::REDEEM,
            ]),
            'api.v1.promotions.offers.assign' => implode(',', [
                PromotionPermission::MANAGE,
                PromotionPermission::REDEEM,
            ]),
        ];

        foreach ($promotionLookups as $name => $expectedPerms) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, "Route [{$name}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$expectedPerms,
                $route->gatherMiddleware(),
                "Route [{$name}] must require promotion permissions.",
            );
        }

        $vehicleServiceLookups = [
            'api.v1.vehicle-service.job-types.lookup' => implode(',', [
                VehicleServicePermission::JOB_TYPES_VIEW,
                VehicleServicePermission::JOBS_VIEW,
                VehicleServicePermission::JOBS_CREATE,
            ]),
            'api.v1.vehicle-service.job-line-items.lookup' => implode(',', [
                VehicleServicePermission::JOBS_CREATE,
                VehicleServicePermission::LINES_MANAGE,
            ]),
        ];

        foreach ($vehicleServiceLookups as $name => $expectedPerms) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, "Route [{$name}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$expectedPerms,
                $route->gatherMiddleware(),
                "Route [{$name}] must require vehicle service permissions.",
            );
        }
    }
}
