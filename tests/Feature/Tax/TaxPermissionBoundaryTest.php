<?php

declare(strict_types=1);

namespace Tests\Feature\Tax;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Tax\Constants\TaxPermission;
use Tests\TestCase;

final class TaxPermissionBoundaryTest extends TestCase
{
    public function test_tax_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (TaxPermission::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('tax', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_tax_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.tax.lookups' => TaxPermission::LOOKUPS_VIEW,
            'api.v1.tax.calculate' => TaxPermission::CALCULATIONS_RUN,
            'api.v1.tax.taxes.index' => TaxPermission::TAXES_VIEW,
            'api.v1.tax.taxes.show' => TaxPermission::TAXES_VIEW,
            'api.v1.tax.taxes.store' => TaxPermission::TAXES_MANAGE,
            'api.v1.tax.taxes.update' => TaxPermission::TAXES_MANAGE,
            'api.v1.tax.taxes.rates.store' => TaxPermission::TAXES_MANAGE,
            'api.v1.tax.groups.index' => TaxPermission::GROUPS_VIEW,
            'api.v1.tax.groups.store' => TaxPermission::GROUPS_MANAGE,
            'api.v1.tax.groups.update' => TaxPermission::GROUPS_MANAGE,
            'api.v1.tax.customer-profiles.index' => TaxPermission::PROFILES_VIEW,
            'api.v1.tax.supplier-profiles.index' => TaxPermission::PROFILES_VIEW,
            'api.v1.tax.customer-profiles.store' => TaxPermission::PROFILES_MANAGE,
            'api.v1.tax.customer-profiles.update' => TaxPermission::PROFILES_MANAGE,
            'api.v1.tax.supplier-profiles.store' => TaxPermission::PROFILES_MANAGE,
            'api.v1.tax.supplier-profiles.update' => TaxPermission::PROFILES_MANAGE,
            'api.v1.tax.posting-profiles.index' => TaxPermission::POSTING_PROFILES_VIEW,
            'api.v1.tax.posting-profiles.store' => TaxPermission::POSTING_PROFILES_MANAGE,
            'api.v1.tax.posting-profiles.update' => TaxPermission::POSTING_PROFILES_MANAGE,
            'api.v1.tax.reports.show' => TaxPermission::REPORTS_VIEW,
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
