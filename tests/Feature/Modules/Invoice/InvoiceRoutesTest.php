<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Invoice;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class InvoiceRoutesTest extends TestCase
{
    public function testInvoiceRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('invoice.invoices.index'));
        self::assertTrue(Route::has('invoice.invoices.store'));
        self::assertTrue(Route::has('invoice.invoices.show'));
        self::assertTrue(Route::has('invoice.invoices.update'));
        self::assertTrue(Route::has('invoice.invoices.destroy'));
        self::assertTrue(Route::has('invoice.invoice-references.index'));
        self::assertTrue(Route::has('invoice.invoice-references.store'));
        self::assertTrue(Route::has('invoice.invoice-references.show'));
        self::assertTrue(Route::has('invoice.invoice-references.update'));
        self::assertTrue(Route::has('invoice.invoice-references.destroy'));
        self::assertTrue(Route::has('invoice.invoice-lines.index'));
        self::assertTrue(Route::has('invoice.invoice-lines.store'));
        self::assertTrue(Route::has('invoice.invoice-lines.show'));
        self::assertTrue(Route::has('invoice.invoice-lines.update'));
        self::assertTrue(Route::has('invoice.invoice-lines.destroy'));
    }

    public function testInvoiceRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('invoice.invoices.index');

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