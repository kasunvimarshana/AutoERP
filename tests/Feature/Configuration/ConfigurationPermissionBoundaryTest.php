<?php

declare(strict_types=1);

namespace Tests\Feature\Configuration;

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Constants\ConfigurationPermission;
use Tests\TestCase;

final class ConfigurationPermissionBoundaryTest extends TestCase
{
    public function test_tenant_configuration_routes_require_granular_tenant_permissions(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        $expected = [
            'api.v1.configuration.definitions' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.resolved' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.tenant.index' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.tenant.show' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.tenant.history' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.tenant.store' => ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            'api.v1.configuration.tenant.update' => ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            'api.v1.configuration.tenant.rollback' => ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            'api.v1.configuration.tenant.destroy' => ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            'api.v1.configuration.organization_unit.index' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.organization_unit.show' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.organization_unit.history' => ConfigurationPermission::ENTRIES_VIEW,
            'api.v1.configuration.organization_unit.store' => ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
            'api.v1.configuration.organization_unit.update' => ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
            'api.v1.configuration.organization_unit.rollback' => ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
            'api.v1.configuration.organization_unit.destroy' => ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
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
