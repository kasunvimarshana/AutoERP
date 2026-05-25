<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Finance;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class FinanceRoutesTest extends TestCase
{
    public function testFinanceRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('finance.accounts.index'));
        self::assertTrue(Route::has('finance.fiscal-years.index'));
        self::assertTrue(Route::has('finance.fiscal-periods.index'));
        self::assertTrue(Route::has('finance.payment-terms.index'));
        self::assertTrue(Route::has('finance.tax-groups.index'));
        self::assertTrue(Route::has('finance.tax-rates.index'));
        self::assertTrue(Route::has('finance.tax-rules.index'));
        self::assertTrue(Route::has('finance.ap-transactions.index'));
        self::assertTrue(Route::has('finance.ar-transactions.index'));
        self::assertTrue(Route::has('finance.cost-centers.index'));
        self::assertTrue(Route::has('finance.journal-entries.index'));
        self::assertTrue(Route::has('finance.journal-entry-lines.index'));
        self::assertTrue(Route::has('finance.budgets.index'));
        self::assertTrue(Route::has('finance.budget-lines.index'));
        self::assertTrue(Route::has('finance.bank-accounts.index'));
        self::assertTrue(Route::has('finance.bank-category-rules.index'));
        self::assertTrue(Route::has('finance.bank-transactions.index'));
        self::assertTrue(Route::has('finance.bank-reconciliations.index'));
    }

    public function testFinanceRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('finance.accounts.index');

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
