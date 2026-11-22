<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\OrganizationUnit;

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Tests\TestCase;

final class OrganizationUnitPermissionBoundaryTest extends TestCase
{
    public function test_organization_unit_routes_require_granular_tenant_permissions(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        $expected = [
            'organization-unit-types.index' => OrganizationUnitPermission::TYPES_VIEW,
            'organization-unit-types.show' => OrganizationUnitPermission::TYPES_VIEW,
            'organization-unit-types.store' => OrganizationUnitPermission::TYPES_MANAGE,
            'organization-unit-types.update' => OrganizationUnitPermission::TYPES_MANAGE,
            'organization-unit-types.destroy' => OrganizationUnitPermission::TYPES_MANAGE,
            'organization-units.index' => OrganizationUnitPermission::VIEW,
            'organization-units.show' => OrganizationUnitPermission::VIEW,
            'organization-units.store' => OrganizationUnitPermission::CREATE,
            'organization-units.update' => OrganizationUnitPermission::UPDATE,
            'organization-units.legal-profile.show' => OrganizationUnitPermission::VIEW,
            'organization-units.legal-profile.update' => OrganizationUnitPermission::UPDATE,
            'organization-units.activate' => OrganizationUnitPermission::ACTIVATE,
            'organization-units.deactivate' => OrganizationUnitPermission::DEACTIVATE,
            'organization-units.retire' => OrganizationUnitPermission::RETIRE,
            'organization-units.logo.update' => OrganizationUnitPermission::UPDATE,
            'organization-units.logo.destroy' => OrganizationUnitPermission::UPDATE,
            'organization-units.documents.download' => OrganizationUnitPermission::DOCUMENTS_VIEW,
            'organization-units.documents.index' => OrganizationUnitPermission::DOCUMENTS_VIEW,
            'organization-units.documents.show' => OrganizationUnitPermission::DOCUMENTS_VIEW,
            'organization-units.documents.store' => OrganizationUnitPermission::DOCUMENTS_MANAGE,
            'organization-units.documents.update' => OrganizationUnitPermission::DOCUMENTS_MANAGE,
            'organization-units.documents.destroy' => OrganizationUnitPermission::DOCUMENTS_MANAGE,
        ];

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
