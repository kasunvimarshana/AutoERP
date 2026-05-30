<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Supplier;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SupplierRoutesTest extends TestCase
{
    public function testSupplierRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('supplier.suppliers.index'));
        self::assertTrue(Route::has('supplier.suppliers.store'));
        self::assertTrue(Route::has('supplier.suppliers.show'));
        self::assertTrue(Route::has('supplier.suppliers.update'));
        self::assertTrue(Route::has('supplier.suppliers.destroy'));
        self::assertTrue(Route::has('supplier.suppliers.lookup'));
        self::assertTrue(Route::has('supplier.suppliers.status'));
        self::assertTrue(Route::has('supplier.suppliers.validate.context'));
        self::assertTrue(Route::has('supplier.suppliers.finance-defaults'));
        self::assertTrue(Route::has('supplier.suppliers.finance-defaults.update'));
        self::assertTrue(Route::has('supplier.suppliers.user-accesses.index'));
        self::assertTrue(Route::has('supplier.suppliers.user-accesses.store'));
        self::assertTrue(Route::has('supplier.suppliers.user-accesses.link-existing'));
        self::assertTrue(Route::has('supplier.suppliers.user-accesses.deactivate'));
        self::assertTrue(Route::has('supplier.suppliers.user-accesses.destroy'));
        self::assertTrue(Route::has('supplier.supplier-categories.index'));
        self::assertTrue(Route::has('supplier.supplier-categories.store'));
        self::assertTrue(Route::has('supplier.supplier-categories.update'));
        self::assertTrue(Route::has('supplier.supplier-categories.destroy'));
        self::assertTrue(Route::has('supplier.suppliers.bank-accounts.index'));
        self::assertTrue(Route::has('supplier.suppliers.bank-accounts.store'));
        self::assertTrue(Route::has('supplier.suppliers.bank-accounts.update'));
        self::assertTrue(Route::has('supplier.suppliers.bank-accounts.destroy'));
        self::assertTrue(Route::has('supplier.suppliers.tax-profile.show'));
        self::assertTrue(Route::has('supplier.suppliers.tax-profile.upsert'));
        self::assertTrue(Route::has('supplier.suppliers.tax-profile.deactivate'));
        self::assertTrue(Route::has('supplier.supplier-contacts.index'));
        self::assertTrue(Route::has('supplier.supplier-contacts.store'));
        self::assertTrue(Route::has('supplier.supplier-contacts.show'));
        self::assertTrue(Route::has('supplier.supplier-contacts.update'));
        self::assertTrue(Route::has('supplier.supplier-contacts.destroy'));
        self::assertTrue(Route::has('supplier.supplier-addresses.index'));
        self::assertTrue(Route::has('supplier.supplier-addresses.store'));
        self::assertTrue(Route::has('supplier.supplier-addresses.show'));
        self::assertTrue(Route::has('supplier.supplier-addresses.update'));
        self::assertTrue(Route::has('supplier.supplier-addresses.destroy'));
        self::assertTrue(Route::has('supplier.supplier-vehicles.index'));
        self::assertTrue(Route::has('supplier.supplier-vehicles.store'));
        self::assertTrue(Route::has('supplier.supplier-vehicles.show'));
        self::assertTrue(Route::has('supplier.supplier-vehicles.update'));
        self::assertTrue(Route::has('supplier.supplier-vehicles.destroy'));
        self::assertTrue(Route::has('supplier.supplier-items.index'));
        self::assertTrue(Route::has('supplier.supplier-items.store'));
        self::assertTrue(Route::has('supplier.supplier-items.show'));
        self::assertTrue(Route::has('supplier.supplier-items.update'));
        self::assertTrue(Route::has('supplier.supplier-items.destroy'));
    }

    public function testSupplierRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('supplier.suppliers.index');

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
