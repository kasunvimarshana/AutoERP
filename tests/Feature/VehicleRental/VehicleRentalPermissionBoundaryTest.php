<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\VehicleRental\Constants\VehicleRentalPermission;
use Tests\TestCase;

final class VehicleRentalPermissionBoundaryTest extends TestCase
{
    public function test_vehicle_rental_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (VehicleRentalPermission::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('vehicle-rental', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_running_chart_calculation_financial_and_report_routes_require_granular_permissions(): void
    {
        $expected = [
            'api.v1.vehicle-rental.running-charts.index' => VehicleRentalPermission::RUNNING_CHARTS_VIEW,
            'api.v1.vehicle-rental.running-charts.show' => VehicleRentalPermission::RUNNING_CHARTS_VIEW,
            'api.v1.vehicle-rental.running-charts.store' => VehicleRentalPermission::RUNNING_CHARTS_MANAGE,
            'api.v1.vehicle-rental.running-charts.update' => VehicleRentalPermission::RUNNING_CHARTS_MANAGE,
            'api.v1.vehicle-rental.running-charts.finalize' => VehicleRentalPermission::RUNNING_CHARTS_MANAGE,
            'api.v1.vehicle-rental.running-charts.reverse' => VehicleRentalPermission::RUNNING_CHARTS_MANAGE,
            'api.v1.vehicle-rental.calculations.index' => VehicleRentalPermission::CALCULATIONS_VIEW,
            'api.v1.vehicle-rental.calculations.show' => VehicleRentalPermission::CALCULATIONS_VIEW,
            'api.v1.vehicle-rental.reports.summary' => VehicleRentalPermission::CALCULATIONS_VIEW,
            'api.v1.vehicle-rental.agreements.calculations.store' => VehicleRentalPermission::CALCULATIONS_MANAGE,
            'api.v1.vehicle-rental.calculations.financial-document.store' => VehicleRentalPermission::FINANCIAL_DOCUMENTS_MANAGE,
            'api.v1.vehicle-rental.calculations.cancel' => VehicleRentalPermission::CALCULATIONS_MANAGE,
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
