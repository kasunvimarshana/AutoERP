<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PurchaseRoutesTest extends TestCase
{
    public function testPurchaseRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('purchase.purchase-orders.index'));
        self::assertTrue(Route::has('purchase.purchase-orders.store'));
        self::assertTrue(Route::has('purchase.purchase-orders.show'));
        self::assertTrue(Route::has('purchase.purchase-orders.update'));
        self::assertTrue(Route::has('purchase.purchase-orders.destroy'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.index'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.store'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.show'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-order-lines.destroy'));
        self::assertTrue(Route::has('purchase.grn-headers.index'));
        self::assertTrue(Route::has('purchase.grn-headers.store'));
        self::assertTrue(Route::has('purchase.grn-headers.show'));
        self::assertTrue(Route::has('purchase.grn-headers.update'));
        self::assertTrue(Route::has('purchase.grn-headers.destroy'));
        self::assertTrue(Route::has('purchase.grn-lines.index'));
        self::assertTrue(Route::has('purchase.grn-lines.store'));
        self::assertTrue(Route::has('purchase.grn-lines.show'));
        self::assertTrue(Route::has('purchase.grn-lines.update'));
        self::assertTrue(Route::has('purchase.grn-lines.destroy'));
        self::assertTrue(Route::has('purchase.purchase-returns.index'));
        self::assertTrue(Route::has('purchase.purchase-returns.store'));
        self::assertTrue(Route::has('purchase.purchase-returns.show'));
        self::assertTrue(Route::has('purchase.purchase-returns.update'));
        self::assertTrue(Route::has('purchase.purchase-returns.destroy'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.index'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.store'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.show'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-return-lines.destroy'));
        self::assertTrue(Route::has('purchase.workflows.transition'));
        self::assertTrue(Route::has('purchase.workflows.document'));
        self::assertTrue(Route::has('purchase.workflows.payment.allocate'));
        self::assertTrue(Route::has('purchase.workflows.inventory.post'));
        self::assertTrue(Route::has('purchase.workflows.finance.post'));
        self::assertTrue(Route::has('purchase.workflows.finance.reverse'));
        self::assertTrue(Route::has('purchase.workflows.history'));
        self::assertTrue(Route::has('purchase.purchase-orders.with-lines.store'));
        self::assertTrue(Route::has('purchase.purchase-orders.with-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-orders.lines.sync'));
        self::assertTrue(Route::has('purchase.grn-headers.with-lines.store'));
        self::assertTrue(Route::has('purchase.grn-headers.with-lines.update'));
        self::assertTrue(Route::has('purchase.grn-headers.lines.sync'));
        self::assertTrue(Route::has('purchase.purchase-returns.with-lines.store'));
        self::assertTrue(Route::has('purchase.purchase-returns.with-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-returns.lines.sync'));
        self::assertTrue(Route::has('purchase.settings.show'));
        self::assertTrue(Route::has('purchase.settings.upsert'));
        self::assertTrue(Route::has('purchase.settings.initialize'));
        self::assertTrue(Route::has('purchase.lookups.purchase-order-lines.available-for-grn'));
        self::assertTrue(Route::has('purchase.lookups.grn-lines.available-for-document'));
        self::assertTrue(Route::has('purchase.lookups.returnable-lines'));
        self::assertTrue(Route::has('purchase.lookups.payable-documents'));
        self::assertTrue(Route::has('purchase.integrations.documents.index'));
        self::assertTrue(Route::has('purchase.integrations.documents.store'));
        self::assertTrue(Route::has('purchase.integrations.documents.show'));
        self::assertTrue(Route::has('purchase.integrations.documents.status'));
        self::assertTrue(Route::has('purchase.integrations.documents.lines.match'));
        self::assertTrue(Route::has('purchase.integrations.documents.lines.unmatch'));
        self::assertTrue(Route::has('purchase.integrations.payments.store'));
        self::assertTrue(Route::has('purchase.integrations.advances.store'));
        self::assertTrue(Route::has('purchase.integrations.payments.allocate'));
        self::assertTrue(Route::has('purchase.integrations.advances.apply'));
        self::assertTrue(Route::has('purchase.integrations.payments.allocations'));
        self::assertTrue(Route::has('purchase.integrations.payments.summary'));
        self::assertTrue(Route::has('purchase.integrations.suppliers.payables'));
        self::assertTrue(Route::has('purchase.integrations.suppliers.advances'));
        self::assertTrue(Route::has('purchase.integrations.payments.post'));
        self::assertTrue(Route::has('purchase.integrations.payments.reverse'));
        self::assertTrue(Route::has('purchase.integrations.payments.refund'));
        self::assertTrue(Route::has('purchase.purchase-invoices.index'));
        self::assertTrue(Route::has('purchase.purchase-invoices.show'));
        self::assertTrue(Route::has('purchase.purchase-invoices.store'));
        self::assertTrue(Route::has('purchase.purchase-invoices.from-po'));
        self::assertTrue(Route::has('purchase.purchase-invoices.from-grn'));
        self::assertTrue(Route::has('purchase.purchase-invoices.from-multiple-grns'));
        self::assertTrue(Route::has('purchase.purchase-invoices.update'));
        self::assertTrue(Route::has('purchase.purchase-invoices.destroy'));
        self::assertTrue(Route::has('purchase.purchase-invoices.post'));
        self::assertTrue(Route::has('purchase.purchase-invoices.cancel'));
        self::assertTrue(Route::has('purchase.purchase-invoices.reverse'));
        self::assertTrue(Route::has('purchase.purchase-invoices.lines.index'));
        self::assertTrue(Route::has('purchase.purchase-invoices.lines.store'));
        self::assertTrue(Route::has('purchase.purchase-invoice-lines.update'));
        self::assertTrue(Route::has('purchase.purchase-invoice-lines.destroy'));
        self::assertTrue(Route::has('purchase.purchase-payments.index'));
        self::assertTrue(Route::has('purchase.purchase-payments.show'));
        self::assertTrue(Route::has('purchase.purchase-payments.store'));
        self::assertTrue(Route::has('purchase.purchase-payments.update'));
        self::assertTrue(Route::has('purchase.purchase-payments.destroy'));
        self::assertTrue(Route::has('purchase.purchase-payments.post'));
        self::assertTrue(Route::has('purchase.purchase-payments.void'));
        self::assertTrue(Route::has('purchase.purchase-payments.reverse'));
        self::assertTrue(Route::has('purchase.purchase-payments.allocate'));
        self::assertTrue(Route::has('purchase.purchase-payments.allocations'));
        self::assertTrue(Route::has('purchase.purchase-advances.store'));
        self::assertTrue(Route::has('purchase.purchase-advances.allocate'));
        self::assertTrue(Route::has('purchase.purchase-refunds.store'));
        self::assertTrue(Route::has('purchase.purchase-write-offs.store'));
        self::assertTrue(Route::has('purchase.supplier-outstanding'));
        self::assertTrue(Route::has('purchase.invoice-payment-status'));
        self::assertTrue(Route::has('purchase.available-po-lines-for-invoice'));
        self::assertTrue(Route::has('purchase.available-grn-lines-for-invoice'));
        self::assertTrue(Route::has('purchase.calculate-invoice'));
        self::assertTrue(Route::has('purchase.validate-uom'));
        self::assertTrue(Route::has('purchase.preview-payment-allocation'));
    }

    public function testPurchaseRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('purchase.purchase-orders.index');

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
