<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Customer;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class CustomerRoutesTest extends TestCase
{
    public function testCustomerRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('customer.customers.index'));
        self::assertTrue(Route::has('customer.customers.store'));
        self::assertTrue(Route::has('customer.customers.show'));
        self::assertTrue(Route::has('customer.customers.update'));
        self::assertTrue(Route::has('customer.customers.destroy'));
        self::assertTrue(Route::has('customer.customers.lookup'));
        self::assertTrue(Route::has('customer.customers.status'));
        self::assertTrue(Route::has('customer.customers.validate.sales'));
        self::assertTrue(Route::has('customer.customers.validate.vehicle-service'));
        self::assertTrue(Route::has('customer.customers.validate.vehicle-rental'));
        self::assertTrue(Route::has('customer.customers.finance-defaults.show'));
        self::assertTrue(Route::has('customer.customers.finance-defaults.update'));
        self::assertTrue(Route::has('customer.customers.credit-check'));
        self::assertTrue(Route::has('customer.customers.tax-profile.show'));
        self::assertTrue(Route::has('customer.customers.user-accesses.index'));
        self::assertTrue(Route::has('customer.customers.user-accesses.store'));
        self::assertTrue(Route::has('customer.customers.user-accesses.link-existing'));
        self::assertTrue(Route::has('customer.customers.user-accesses.deactivate'));
        self::assertTrue(Route::has('customer.customers.user-accesses.destroy'));
        self::assertTrue(Route::has('customer.customer-contacts.index'));
        self::assertTrue(Route::has('customer.customer-contacts.store'));
        self::assertTrue(Route::has('customer.customer-contacts.show'));
        self::assertTrue(Route::has('customer.customer-contacts.update'));
        self::assertTrue(Route::has('customer.customer-contacts.destroy'));
        self::assertTrue(Route::has('customer.customer-addresses.index'));
        self::assertTrue(Route::has('customer.customer-addresses.store'));
        self::assertTrue(Route::has('customer.customer-addresses.show'));
        self::assertTrue(Route::has('customer.customer-addresses.update'));
        self::assertTrue(Route::has('customer.customer-addresses.destroy'));
        self::assertTrue(Route::has('customer.customer-vehicles.index'));
        self::assertTrue(Route::has('customer.customer-vehicles.store'));
        self::assertTrue(Route::has('customer.customer-vehicles.show'));
        self::assertTrue(Route::has('customer.customer-vehicles.update'));
        self::assertTrue(Route::has('customer.customer-vehicles.destroy'));
    }

    public function testCustomerRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('customer.customers.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
        self::assertContains(
            (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
            $middlewares,
        );
    }
}
