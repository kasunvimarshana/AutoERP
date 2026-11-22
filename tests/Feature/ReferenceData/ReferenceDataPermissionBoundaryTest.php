<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\ReferenceData\Constants\ReferenceDataPermission;
use Tests\TestCase;

final class ReferenceDataPermissionBoundaryTest extends TestCase
{
    public function test_reference_data_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (ReferenceDataPermission::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('reference-data', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_reference_data_routes_require_granular_tenant_permissions(): void
    {
        $catalogs = ['countries', 'currencies', 'languages', 'timezones'];
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        foreach ($catalogs as $catalog) {
            $expected = [
                "api.v1.{$catalog}.index" => ReferenceDataPermission::VIEW,
                "api.v1.{$catalog}.show" => ReferenceDataPermission::VIEW,
                "api.v1.{$catalog}.store" => ReferenceDataPermission::MANAGE,
                "api.v1.{$catalog}.update" => ReferenceDataPermission::MANAGE,
                "api.v1.{$catalog}.status" => ReferenceDataPermission::MANAGE,
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
}
