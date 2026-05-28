<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SalesRoutesTest extends TestCase
{
    public function testSalesRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('sales.sales-orders.index'));
        self::assertTrue(Route::has('sales.sales-orders.store'));
        self::assertTrue(Route::has('sales.sales-orders.show'));
        self::assertTrue(Route::has('sales.sales-orders.update'));
        self::assertTrue(Route::has('sales.sales-orders.destroy'));
        self::assertTrue(Route::has('sales.sales-order-lines.index'));
        self::assertTrue(Route::has('sales.sales-order-lines.store'));
        self::assertTrue(Route::has('sales.sales-order-lines.show'));
        self::assertTrue(Route::has('sales.sales-order-lines.update'));
        self::assertTrue(Route::has('sales.sales-order-lines.destroy'));
        self::assertTrue(Route::has('sales.gdn-headers.index'));
        self::assertTrue(Route::has('sales.gdn-headers.store'));
        self::assertTrue(Route::has('sales.gdn-headers.show'));
        self::assertTrue(Route::has('sales.gdn-headers.update'));
        self::assertTrue(Route::has('sales.gdn-headers.destroy'));
        self::assertTrue(Route::has('sales.gdn-lines.index'));
        self::assertTrue(Route::has('sales.gdn-lines.store'));
        self::assertTrue(Route::has('sales.gdn-lines.show'));
        self::assertTrue(Route::has('sales.gdn-lines.update'));
        self::assertTrue(Route::has('sales.gdn-lines.destroy'));
        self::assertTrue(Route::has('sales.sales-returns.index'));
        self::assertTrue(Route::has('sales.sales-returns.store'));
        self::assertTrue(Route::has('sales.sales-returns.show'));
        self::assertTrue(Route::has('sales.sales-returns.update'));
        self::assertTrue(Route::has('sales.sales-returns.destroy'));
        self::assertTrue(Route::has('sales.sales-return-lines.index'));
        self::assertTrue(Route::has('sales.sales-return-lines.store'));
        self::assertTrue(Route::has('sales.sales-return-lines.show'));
        self::assertTrue(Route::has('sales.sales-return-lines.update'));
        self::assertTrue(Route::has('sales.sales-return-lines.destroy'));
        self::assertTrue(Route::has('sales.workflows.transition'));
        self::assertTrue(Route::has('sales.workflows.document'));
        self::assertTrue(Route::has('sales.workflows.payment.allocate'));
        self::assertTrue(Route::has('sales.workflows.inventory.post'));
        self::assertTrue(Route::has('sales.workflows.finance.post'));
        self::assertTrue(Route::has('sales.workflows.finance.reverse'));
        self::assertTrue(Route::has('sales.workflows.history'));
        self::assertTrue(Route::has('sales.sales-orders.with-lines.store'));
        self::assertTrue(Route::has('sales.sales-orders.with-lines.update'));
        self::assertTrue(Route::has('sales.sales-orders.lines.sync'));
        self::assertTrue(Route::has('sales.gdn-headers.with-lines.store'));
        self::assertTrue(Route::has('sales.gdn-headers.with-lines.update'));
        self::assertTrue(Route::has('sales.gdn-headers.lines.sync'));
        self::assertTrue(Route::has('sales.sales-returns.with-lines.store'));
        self::assertTrue(Route::has('sales.sales-returns.with-lines.update'));
        self::assertTrue(Route::has('sales.sales-returns.lines.sync'));
        self::assertTrue(Route::has('sales.settings.show'));
        self::assertTrue(Route::has('sales.settings.upsert'));
        self::assertTrue(Route::has('sales.settings.initialize'));
        self::assertTrue(Route::has('sales.lookups.sales-order-lines.available-for-gdn'));
        self::assertTrue(Route::has('sales.lookups.gdn-lines.available-for-document'));
        self::assertTrue(Route::has('sales.lookups.returnable-lines'));
        self::assertTrue(Route::has('sales.lookups.receivable-documents'));
        self::assertTrue(Route::has('sales.integrations.documents.index'));
        self::assertTrue(Route::has('sales.integrations.documents.store'));
        self::assertTrue(Route::has('sales.integrations.documents.show'));
        self::assertTrue(Route::has('sales.integrations.documents.status'));
        self::assertTrue(Route::has('sales.integrations.documents.lines.match'));
        self::assertTrue(Route::has('sales.integrations.documents.lines.unmatch'));
        self::assertTrue(Route::has('sales.integrations.payments.store'));
        self::assertTrue(Route::has('sales.integrations.advances.store'));
        self::assertTrue(Route::has('sales.integrations.payments.allocate'));
        self::assertTrue(Route::has('sales.integrations.advances.apply'));
        self::assertTrue(Route::has('sales.integrations.payments.allocations'));
        self::assertTrue(Route::has('sales.integrations.payments.summary'));
        self::assertTrue(Route::has('sales.integrations.customers.receivables'));
        self::assertTrue(Route::has('sales.integrations.customers.advances'));
        self::assertTrue(Route::has('sales.integrations.payments.post'));
        self::assertTrue(Route::has('sales.integrations.payments.reverse'));
        self::assertTrue(Route::has('sales.integrations.payments.refund'));
        self::assertTrue(Route::has('sales.sales-invoices.index'));
        self::assertTrue(Route::has('sales.sales-invoices.show'));
        self::assertTrue(Route::has('sales.sales-invoices.store'));
        self::assertTrue(Route::has('sales.sales-invoices.from-so'));
        self::assertTrue(Route::has('sales.sales-invoices.from-gdn'));
        self::assertTrue(Route::has('sales.sales-invoices.from-multiple-gdns'));
        self::assertTrue(Route::has('sales.sales-invoices.update'));
        self::assertTrue(Route::has('sales.sales-invoices.destroy'));
        self::assertTrue(Route::has('sales.sales-invoices.post'));
        self::assertTrue(Route::has('sales.sales-invoices.cancel'));
        self::assertTrue(Route::has('sales.sales-invoices.reverse'));
        self::assertTrue(Route::has('sales.sales-invoices.lines.index'));
        self::assertTrue(Route::has('sales.sales-invoices.lines.store'));
        self::assertTrue(Route::has('sales.sales-invoice-lines.update'));
        self::assertTrue(Route::has('sales.sales-invoice-lines.destroy'));
        self::assertTrue(Route::has('sales.sales-payments.index'));
        self::assertTrue(Route::has('sales.sales-payments.show'));
        self::assertTrue(Route::has('sales.sales-payments.store'));
        self::assertTrue(Route::has('sales.sales-payments.update'));
        self::assertTrue(Route::has('sales.sales-payments.destroy'));
        self::assertTrue(Route::has('sales.sales-payments.post'));
        self::assertTrue(Route::has('sales.sales-payments.void'));
        self::assertTrue(Route::has('sales.sales-payments.reverse'));
        self::assertTrue(Route::has('sales.sales-payments.allocate'));
        self::assertTrue(Route::has('sales.sales-payments.allocations'));
        self::assertTrue(Route::has('sales.sales-advances.store'));
        self::assertTrue(Route::has('sales.sales-advances.allocate'));
        self::assertTrue(Route::has('sales.sales-refunds.store'));
        self::assertTrue(Route::has('sales.sales-write-offs.store'));
        self::assertTrue(Route::has('sales.customer-outstanding'));
        self::assertTrue(Route::has('sales.invoice-payment-status'));
        self::assertTrue(Route::has('sales.available-so-lines-for-invoice'));
        self::assertTrue(Route::has('sales.available-gdn-lines-for-invoice'));
        self::assertTrue(Route::has('sales.calculate-invoice'));
        self::assertTrue(Route::has('sales.validate-uom'));
        self::assertTrue(Route::has('sales.preview-payment-allocation'));
    }

    public function testSalesRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('sales.sales-orders.index');

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
