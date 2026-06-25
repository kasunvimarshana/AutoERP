<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\FastSalesController;
use Modules\Sales\Http\Controllers\SalesAllocationController;
use Modules\Sales\Http\Controllers\SalesContextController;
use Modules\Sales\Http\Controllers\SalesCreditNoteController;
use Modules\Sales\Http\Controllers\SalesDeliveryController;
use Modules\Sales\Http\Controllers\SalesEligibilityController;
use Modules\Sales\Http\Controllers\SalesIntegrationController;
use Modules\Sales\Http\Controllers\SalesOrderController;
use Modules\Sales\Http\Controllers\SalesQuotationController;
use Modules\Sales\Http\Controllers\SalesReturnController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:sales',
];

Route::prefix('api/v1/sales')->middleware($middleware)->name('api.v1.sales.')->group(function (): void {
    Route::get('fast-sales/context', [FastSalesController::class, 'context'])->name('fast-sales.context');
    Route::post('fast-sales/preview', [FastSalesController::class, 'preview'])->name('fast-sales.preview');
    Route::post('fast-sales', [FastSalesController::class, 'store'])->name('fast-sales.store');

    Route::get('orders/create-context', [SalesContextController::class, 'orderCreateContext'])->name('orders.create-context');
    Route::get('items/{item}/sales-context', [SalesContextController::class, 'itemContext'])->whereNumber('item')->name('items.sales-context');
    Route::get('adjustments/catalogue', [SalesContextController::class, 'adjustmentCatalogue'])->name('adjustments.catalogue');
    Route::get('warehouses', [SalesContextController::class, 'warehouses'])->name('warehouses.index');
    Route::get('warehouses/{warehouse}/locations', [SalesContextController::class, 'warehouseLocations'])->whereNumber('warehouse')->name('warehouses.locations');
    Route::get('tax-groups', [SalesContextController::class, 'taxGroups'])->name('tax-groups.index');

    Route::get('eligible/allocatable-sales-orders', [SalesEligibilityController::class, 'allocatableSalesOrders'])->name('eligible.allocatable-sales-orders');
    Route::get('eligible/deliverable-sales-orders', [SalesEligibilityController::class, 'deliverableSalesOrders'])->name('eligible.deliverable-sales-orders');
    Route::get('eligible/invoiceable-sales-orders', [SalesEligibilityController::class, 'invoiceableSalesOrders'])->name('eligible.invoiceable-sales-orders');
    Route::get('eligible/invoiceable-sales-deliveries', [SalesEligibilityController::class, 'invoiceableSalesDeliveries'])->name('eligible.invoiceable-sales-deliveries');
    Route::get('eligible/returnable-sales-deliveries', [SalesEligibilityController::class, 'returnableSalesDeliveries'])->name('eligible.returnable-sales-deliveries');
    Route::get('eligible/outstanding-customer-invoices', [SalesEligibilityController::class, 'outstandingCustomerInvoices'])->name('eligible.outstanding-customer-invoices');

    Route::get('quotations', [SalesQuotationController::class, 'index'])->name('quotations.index');
    Route::post('quotations', [SalesQuotationController::class, 'store'])->name('quotations.store');
    Route::get('quotations/{quotation}', [SalesQuotationController::class, 'show'])->whereNumber('quotation')->name('quotations.show');
    Route::put('quotations/{quotation}', [SalesQuotationController::class, 'update'])->whereNumber('quotation')->name('quotations.update');
    Route::delete('quotations/{quotation}', [SalesQuotationController::class, 'destroy'])->whereNumber('quotation')->name('quotations.destroy');
    Route::patch('quotations/{quotation}/send', [SalesQuotationController::class, 'send'])->whereNumber('quotation')->name('quotations.send');
    Route::patch('quotations/{quotation}/accept', [SalesQuotationController::class, 'accept'])->whereNumber('quotation')->name('quotations.accept');
    Route::patch('quotations/{quotation}/reject', [SalesQuotationController::class, 'reject'])->whereNumber('quotation')->name('quotations.reject');
    Route::post('quotations/{quotation}/convert-to-order', [SalesQuotationController::class, 'convert'])->whereNumber('quotation')->name('quotations.convert');

    Route::get('orders', [SalesOrderController::class, 'index'])->name('orders.index');
    Route::post('orders', [SalesOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [SalesOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::put('orders/{order}', [SalesOrderController::class, 'update'])->whereNumber('order')->name('orders.update');
    Route::delete('orders/{order}', [SalesOrderController::class, 'destroy'])->whereNumber('order')->name('orders.destroy');
    Route::patch('orders/{order}/submit', [SalesOrderController::class, 'submit'])->whereNumber('order')->name('orders.submit');
    Route::patch('orders/{order}/approve', [SalesOrderController::class, 'approve'])->whereNumber('order')->name('orders.approve');
    Route::patch('orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
    Route::patch('orders/{order}/close', [SalesOrderController::class, 'close'])->whereNumber('order')->name('orders.close');
    Route::get('orders/{order}/allocatable-lines', [SalesOrderController::class, 'allocatableLines'])->whereNumber('order')->name('orders.allocatable-lines');
    Route::get('orders/{order}/deliverable-lines', [SalesOrderController::class, 'deliverableLines'])->whereNumber('order')->name('orders.deliverable-lines');
    Route::get('orders/{order}/invoiceable-lines', [SalesOrderController::class, 'invoiceableLines'])->whereNumber('order')->name('orders.invoiceable-lines');

    Route::get('allocations', [SalesAllocationController::class, 'index'])->name('allocations.index');
    Route::post('allocations', [SalesAllocationController::class, 'store'])->name('allocations.store');
    Route::get('allocations/{allocation}', [SalesAllocationController::class, 'show'])->whereNumber('allocation')->name('allocations.show');
    Route::patch('allocations/{allocation}/release', [SalesAllocationController::class, 'release'])->whereNumber('allocation')->name('allocations.release');

    Route::get('deliveries', [SalesDeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('deliveries', [SalesDeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('deliveries/{delivery}', [SalesDeliveryController::class, 'show'])->whereNumber('delivery')->name('deliveries.show');
    Route::patch('deliveries/{delivery}/post', [SalesDeliveryController::class, 'post'])->whereNumber('delivery')->name('deliveries.post');
    Route::patch('deliveries/{delivery}/reverse', [SalesDeliveryController::class, 'reverse'])->whereNumber('delivery')->name('deliveries.reverse');
    Route::get('deliveries/{delivery}/returnable-lines', [SalesDeliveryController::class, 'returnableLines'])->whereNumber('delivery')->name('deliveries.returnable-lines');

    Route::post('invoices/preview', [SalesIntegrationController::class, 'previewInvoice'])->name('invoices.preview');
    Route::post('invoices', [SalesIntegrationController::class, 'createInvoice'])->name('invoices.store');
    Route::post('payments/prepare', [SalesIntegrationController::class, 'preparePayment'])->name('payments.prepare');

    Route::get('returns', [SalesReturnController::class, 'index'])->name('returns.index');
    Route::post('returns', [SalesReturnController::class, 'store'])->name('returns.store');
    Route::get('returns/{return}', [SalesReturnController::class, 'show'])->whereNumber('return')->name('returns.show');
    Route::patch('returns/{return}/approve', [SalesReturnController::class, 'approve'])->whereNumber('return')->name('returns.approve');
    Route::patch('returns/{return}/post', [SalesReturnController::class, 'post'])->whereNumber('return')->name('returns.post');
    Route::patch('returns/{return}/cancel', [SalesReturnController::class, 'cancel'])->whereNumber('return')->name('returns.cancel');

    Route::get('credit-notes', [SalesCreditNoteController::class, 'index'])->name('credit-notes.index');
    Route::post('credit-notes', [SalesCreditNoteController::class, 'store'])->name('credit-notes.store');
    Route::get('credit-notes/{creditNote}', [SalesCreditNoteController::class, 'show'])->whereNumber('creditNote')->name('credit-notes.show');
    Route::patch('credit-notes/{creditNote}/approve', [SalesCreditNoteController::class, 'approve'])->whereNumber('creditNote')->name('credit-notes.approve');
    Route::patch('credit-notes/{creditNote}/post', [SalesCreditNoteController::class, 'post'])->whereNumber('creditNote')->name('credit-notes.post');
    Route::post('credit-notes/{creditNote}/allocations', [SalesCreditNoteController::class, 'allocate'])->whereNumber('creditNote')->name('credit-notes.allocations.store');
});
