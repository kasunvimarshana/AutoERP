<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Customer\Services\CustomerAuthorizationService;
use Tests\TestCase;

final class CustomerPermissionBoundaryTest extends TestCase
{
    public function test_customer_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (CustomerAuthorizationService::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('customer', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_customer_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.customers.lookup' => CustomerAuthorizationService::VIEW,
            'api.v1.customers.index' => CustomerAuthorizationService::VIEW,
            'api.v1.customers.show' => CustomerAuthorizationService::VIEW,
            'api.v1.customers.store' => CustomerAuthorizationService::CREATE,
            'api.v1.customers.with-relations.store' => CustomerAuthorizationService::CREATE,
            'api.v1.customers.update' => CustomerAuthorizationService::UPDATE,
            'api.v1.customers.activate' => CustomerAuthorizationService::UPDATE,
            'api.v1.customers.deactivate' => CustomerAuthorizationService::UPDATE,
            'api.v1.customers.status' => CustomerAuthorizationService::UPDATE,
            'api.v1.customers.destroy' => CustomerAuthorizationService::DELETE,
            'api.v1.customer-categories.lookup' => CustomerAuthorizationService::VIEW,
            'api.v1.customer-categories.index' => CustomerAuthorizationService::VIEW,
            'api.v1.customer-categories.show' => CustomerAuthorizationService::VIEW,
            'api.v1.customer-categories.store' => CustomerAuthorizationService::UPDATE,
            'api.v1.customer-categories.update' => CustomerAuthorizationService::UPDATE,
            'api.v1.customer-categories.destroy' => CustomerAuthorizationService::UPDATE,
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
        foreach (['contacts', 'addresses', 'bank-accounts', 'categories', 'documents'] as $relation) {
            $expected["api.v1.customers.{$relation}.index"] = CustomerAuthorizationService::VIEW;
            $expected["api.v1.customers.{$relation}.store"] = CustomerAuthorizationService::UPDATE;
            if ($relation !== 'categories') {
                $expected["api.v1.customers.{$relation}.update"] = CustomerAuthorizationService::UPDATE;
            }
            $expected["api.v1.customers.{$relation}.destroy"] = CustomerAuthorizationService::UPDATE;
        }
        $expected['api.v1.customers.credit-profile.show'] = CustomerAuthorizationService::VIEW;
        $expected['api.v1.customers.credit-profile.update'] = CustomerAuthorizationService::UPDATE;
        $expected['api.v1.customers.status-history.index'] = CustomerAuthorizationService::VIEW;

        return $expected;
    }
}
