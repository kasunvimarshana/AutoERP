<?php

declare(strict_types=1);

namespace Tests\Feature\Hr;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Hr\Services\HrAuthorizationService;
use Tests\TestCase;

final class HrPermissionBoundaryTest extends TestCase
{
    public function test_hr_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (HrAuthorizationService::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('hr', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_hr_routes_require_hr_tenant_feature(): void
    {
        $featureMiddleware = (string) config('tenant.entitlements.middleware_alias', 'tenant.feature')
            .':'.TenantFeature::HR;

        foreach (array_keys($this->expectedRoutePermissions()) as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains(
                $featureMiddleware,
                $route->gatherMiddleware(),
                "Route [{$routeName}] must require the HR tenant feature.",
            );
        }
    }

    public function test_hr_routes_require_granular_tenant_permissions(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        foreach ($this->expectedRoutePermissions() as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$permission,
                $route->gatherMiddleware(),
                "Route [{$routeName}] must require [{$permission}].",
            );
        }
    }

    public function test_hr_employee_rate_routes_are_create_only(): void
    {
        self::assertNotNull(Route::getRoutes()->getByName('api.v1.hr.employees.rates.index'));
        self::assertNotNull(Route::getRoutes()->getByName('api.v1.hr.employees.rates.store'));
        self::assertNull(Route::getRoutes()->getByName('api.v1.hr.employees.rates.update'));
        self::assertNull(Route::getRoutes()->getByName('api.v1.hr.employees.rates.destroy'));
    }

    /** @return array<string,string> */
    private function expectedRoutePermissions(): array
    {
        return [
            'api.v1.hr.employees.lookup' => HrAuthorizationService::VIEW_EMPLOYEES,
            'api.v1.hr.employees.index' => HrAuthorizationService::VIEW_EMPLOYEES,
            'api.v1.hr.employees.show' => HrAuthorizationService::VIEW_EMPLOYEES,
            'api.v1.hr.employees.store' => HrAuthorizationService::CREATE_EMPLOYEES,
            'api.v1.hr.employees.with-relations.store' => HrAuthorizationService::CREATE_EMPLOYEES,
            'api.v1.hr.employees.update' => HrAuthorizationService::UPDATE_EMPLOYEES,
            'api.v1.hr.employees.activate' => HrAuthorizationService::UPDATE_EMPLOYEES,
            'api.v1.hr.employees.deactivate' => HrAuthorizationService::UPDATE_EMPLOYEES,
            'api.v1.hr.employees.status' => HrAuthorizationService::UPDATE_EMPLOYEES,
            'api.v1.hr.employees.availability.update' => HrAuthorizationService::UPDATE_EMPLOYEES,
            'api.v1.hr.employees.availability.show' => HrAuthorizationService::VIEW_EMPLOYEES,
            'api.v1.hr.employees.availability.store' => HrAuthorizationService::UPDATE_EMPLOYEES,
            'api.v1.hr.employees.status-history.index' => HrAuthorizationService::VIEW_EMPLOYEES,
            'api.v1.hr.employees.destroy' => HrAuthorizationService::DELETE_EMPLOYEES,
            ...$this->employeeRelationRoutePermissions(),
            ...$this->masterDataRoutePermissions(),
        ];
    }

    /** @return array<string,string> */
    private function employeeRelationRoutePermissions(): array
    {
        $expected = [
            'api.v1.hr.employees.rates.index' => HrAuthorizationService::VIEW_EMPLOYEES,
            'api.v1.hr.employees.rates.store' => HrAuthorizationService::UPDATE_EMPLOYEES,
        ];

        foreach (['contacts', 'addresses', 'documents', 'skills', 'certifications', 'licenses'] as $relation) {
            $expected["api.v1.hr.employees.{$relation}.index"] = HrAuthorizationService::VIEW_EMPLOYEES;
            $expected["api.v1.hr.employees.{$relation}.store"] = HrAuthorizationService::UPDATE_EMPLOYEES;
            $expected["api.v1.hr.employees.{$relation}.update"] = HrAuthorizationService::UPDATE_EMPLOYEES;
            $expected["api.v1.hr.employees.{$relation}.destroy"] = HrAuthorizationService::UPDATE_EMPLOYEES;
        }

        return $expected;
    }

    /** @return array<string,string> */
    private function masterDataRoutePermissions(): array
    {
        $expected = [];

        foreach (['departments', 'designations', 'employment-types', 'skills', 'certifications', 'licenses'] as $resource) {
            $expected["api.v1.hr.{$resource}.lookup"] = HrAuthorizationService::VIEW_MASTER_DATA;
            $expected["api.v1.hr.{$resource}.index"] = HrAuthorizationService::VIEW_MASTER_DATA;
            $expected["api.v1.hr.{$resource}.show"] = HrAuthorizationService::VIEW_MASTER_DATA;
            $expected["api.v1.hr.{$resource}.store"] = HrAuthorizationService::MANAGE_MASTER_DATA;
            $expected["api.v1.hr.{$resource}.update"] = HrAuthorizationService::MANAGE_MASTER_DATA;
            $expected["api.v1.hr.{$resource}.destroy"] = HrAuthorizationService::MANAGE_MASTER_DATA;
        }

        return $expected;
    }
}
