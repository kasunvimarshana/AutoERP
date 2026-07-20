<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Modules\VehicleRental\Constants\VehicleRentalPermission;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Tests\TestCase;

final class RentalCrudInterfaceBoundaryTest extends TestCase
{
    public function test_agreement_form_lookup_uses_vehicle_rental_manage_permission(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.vehicle-rental.lookups.agreement-form');
        self::assertNotNull($route);

        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
        self::assertContains(
            $permissionMiddleware.':'.VehicleRentalPermission::AGREEMENTS_MANAGE,
            $route->gatherMiddleware(),
        );
    }

    public function test_workflow_lookups_use_their_owned_manage_permissions(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
        $expected = [
            'api.v1.vehicle-rental.lookups.assignment-agreements' => VehicleRentalPermission::ASSIGNMENTS_MANAGE,
            'api.v1.vehicle-rental.lookups.assignment-sources' => VehicleRentalPermission::ASSIGNMENTS_MANAGE,
            'api.v1.vehicle-rental.lookups.running-chart-assignments' => VehicleRentalPermission::RUNNING_CHARTS_MANAGE,
            'api.v1.vehicle-rental.lookups.calculation-agreements' => VehicleRentalPermission::CALCULATIONS_MANAGE,
        ];

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);
            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains($permissionMiddleware.':'.$permission, $route->gatherMiddleware());
        }
    }

    public function test_assignment_status_filter_accepts_only_domain_statuses(): void
    {
        $rules = (new ListRentalRequest())->rules();
        $assignmentStatusRules = ['assignment_status' => $rules['assignment_status']];

        self::assertFalse(Validator::make(
            ['assignment_status' => 'returned'],
            $assignmentStatusRules,
        )->fails());
        self::assertTrue(Validator::make(
            ['assignment_status' => 'deleted'],
            $assignmentStatusRules,
        )->fails());
    }
}
