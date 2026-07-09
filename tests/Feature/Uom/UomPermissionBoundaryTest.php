<?php

declare(strict_types=1);

namespace Tests\Feature\Uom;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\UOM\Constants\UomPermission;
use Tests\TestCase;

final class UomPermissionBoundaryTest extends TestCase
{
    public function test_uom_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (UomPermission::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('uom', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_uom_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.uoms.lookup' => UomPermission::UOMS_VIEW,
            'api.v1.uoms.base' => UomPermission::UOMS_VIEW,
            'api.v1.uoms.categories' => UomPermission::UOMS_VIEW,
            'api.v1.uoms.types' => UomPermission::UOMS_VIEW,
            'api.v1.uoms.index' => UomPermission::UOMS_VIEW,
            'api.v1.uoms.show' => UomPermission::UOMS_VIEW,
            'api.v1.uoms.store' => UomPermission::UOMS_CREATE,
            'api.v1.uoms.update' => UomPermission::UOMS_UPDATE,
            'api.v1.uoms.activate' => UomPermission::UOMS_ACTIVATE,
            'api.v1.uoms.deactivate' => UomPermission::UOMS_DEACTIVATE,
            'api.v1.uoms.destroy' => UomPermission::UOMS_DELETE,
            'api.v1.uom-conversions.convert' => UomPermission::CONVERSIONS_RUN,
            'api.v1.uom-conversions.index' => UomPermission::CONVERSIONS_VIEW,
            'api.v1.uom-conversions.show' => UomPermission::CONVERSIONS_VIEW,
            'api.v1.uom-conversions.store' => UomPermission::CONVERSIONS_CREATE,
            'api.v1.uom-conversions.update' => UomPermission::CONVERSIONS_UPDATE,
            'api.v1.uom-conversions.activate' => UomPermission::CONVERSIONS_ACTIVATE,
            'api.v1.uom-conversions.deactivate' => UomPermission::CONVERSIONS_DEACTIVATE,
            'api.v1.uom-conversions.destroy' => UomPermission::CONVERSIONS_DELETE,
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
