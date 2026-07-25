<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Tests\TestCase;

final class ReportingPermissionBoundaryTest extends TestCase
{
    public function test_reporting_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (ReportingAuthorizationService::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('reporting', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_reporting_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.reports.index' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.summary' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.purchase.detailed' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.vehicle-service.detailed' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.vehicle-service.employee-incentives' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.vehicle-service.technician-work' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.vehicle-service.employee-commissions' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.show' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.run' => ReportingAuthorizationService::REPORTS_VIEW,
            'api.v1.reports.purchase.detailed.export' => ReportingAuthorizationService::REPORTS_EXPORT,
            'api.v1.reports.vehicle-service.detailed.export' => ReportingAuthorizationService::REPORTS_EXPORT,
            'api.v1.reports.vehicle-service.employee-incentives.export' => ReportingAuthorizationService::REPORTS_EXPORT,
            'api.v1.reports.vehicle-service.technician-work.export' => ReportingAuthorizationService::REPORTS_EXPORT,
            'api.v1.reports.vehicle-service.employee-commissions.export' => ReportingAuthorizationService::REPORTS_EXPORT,
            'api.v1.reports.export' => ReportingAuthorizationService::REPORTS_EXPORT,
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
