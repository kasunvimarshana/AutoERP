<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Modules\VehicleRental\Constants\VehicleRentalPermission;
use Modules\VehicleRental\Http\Requests\DeleteRentalAgreementRequest;
use Modules\VehicleRental\Http\Requests\DeleteRentalAssignmentRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalAssignmentRequest;
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

    public function test_draft_agreement_delete_uses_manage_permission_and_optimistic_version(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.vehicle-rental.agreements.destroy');
        self::assertNotNull($route);
        self::assertContains('DELETE', $route->methods());

        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
        self::assertContains(
            $permissionMiddleware.':'.VehicleRentalPermission::AGREEMENTS_MANAGE,
            $route->gatherMiddleware(),
        );

        $rules = (new DeleteRentalAgreementRequest())->rules();
        self::assertFalse(Validator::make(['expected_version' => 1], $rules)->fails());
        self::assertTrue(Validator::make([], $rules)->fails());
        self::assertTrue(Validator::make(['expected_version' => 0], $rules)->fails());
    }

    public function test_planned_assignment_update_and_delete_use_manage_permission_and_optimistic_version(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
        $expectedRoutes = [
            'api.v1.vehicle-rental.assignments.update' => 'PUT',
            'api.v1.vehicle-rental.assignments.destroy' => 'DELETE',
        ];

        foreach ($expectedRoutes as $routeName => $method) {
            $route = Route::getRoutes()->getByName($routeName);
            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains($method, $route->methods());
            self::assertContains(
                $permissionMiddleware.':'.VehicleRentalPermission::ASSIGNMENTS_MANAGE,
                $route->gatherMiddleware(),
            );
        }

        $deleteRules = (new DeleteRentalAssignmentRequest())->rules();
        self::assertFalse(Validator::make(['expected_version' => 1], $deleteRules)->fails());
        self::assertTrue(Validator::make([], $deleteRules)->fails());
        self::assertTrue(Validator::make(['expected_version' => 0], $deleteRules)->fails());

        $updateRequest = new UpdateRentalAssignmentRequest();
        $updateRequest->attributes->set(
            (string) config('core.current_tenant.id_attribute', 'current_tenant_id'),
            1,
        );
        $updateRules = $updateRequest->rules();
        self::assertArrayHasKey('expected_version', $updateRules);
        self::assertArrayHasKey('agreement_id', $updateRules);
        self::assertArrayHasKey('vehicle_id', $updateRules);
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
