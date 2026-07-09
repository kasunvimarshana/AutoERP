<?php

declare(strict_types=1);

namespace Tests\Feature\Supplier;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Tests\TestCase;

final class SupplierPermissionBoundaryTest extends TestCase
{
    public function test_supplier_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (SupplierAuthorizationService::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('supplier', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_supplier_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.suppliers.lookup' => SupplierAuthorizationService::VIEW,
            'api.v1.suppliers.index' => SupplierAuthorizationService::VIEW,
            'api.v1.suppliers.show' => SupplierAuthorizationService::VIEW,
            'api.v1.suppliers.store' => SupplierAuthorizationService::CREATE,
            'api.v1.suppliers.with-relations.store' => SupplierAuthorizationService::CREATE,
            'api.v1.suppliers.update' => SupplierAuthorizationService::UPDATE,
            'api.v1.suppliers.activate' => SupplierAuthorizationService::UPDATE,
            'api.v1.suppliers.deactivate' => SupplierAuthorizationService::UPDATE,
            'api.v1.suppliers.status' => SupplierAuthorizationService::UPDATE,
            'api.v1.suppliers.destroy' => SupplierAuthorizationService::DELETE,
            'api.v1.supplier-categories.lookup' => SupplierAuthorizationService::VIEW,
            'api.v1.supplier-categories.index' => SupplierAuthorizationService::VIEW,
            'api.v1.supplier-categories.show' => SupplierAuthorizationService::VIEW,
            'api.v1.supplier-categories.store' => SupplierAuthorizationService::UPDATE,
            'api.v1.supplier-categories.update' => SupplierAuthorizationService::UPDATE,
            'api.v1.supplier-categories.destroy' => SupplierAuthorizationService::UPDATE,
            ...$this->relationRoutes(),
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

    /** @return array<string, string> */
    private function relationRoutes(): array
    {
        $expected = [];
        foreach (['contacts', 'addresses', 'bank-accounts', 'categories', 'documents', 'item-mappings'] as $relation) {
            $expected["api.v1.suppliers.{$relation}.index"] = SupplierAuthorizationService::VIEW;
            $expected["api.v1.suppliers.{$relation}.store"] = SupplierAuthorizationService::UPDATE;
            if ($relation !== 'categories') {
                $expected["api.v1.suppliers.{$relation}.update"] = SupplierAuthorizationService::UPDATE;
            }
            $expected["api.v1.suppliers.{$relation}.destroy"] = SupplierAuthorizationService::UPDATE;
        }
        $expected['api.v1.suppliers.credit-profile.show'] = SupplierAuthorizationService::VIEW;
        $expected['api.v1.suppliers.credit-profile.update'] = SupplierAuthorizationService::UPDATE;
        $expected['api.v1.suppliers.status-history.index'] = SupplierAuthorizationService::VIEW;

        return $expected;
    }
}
