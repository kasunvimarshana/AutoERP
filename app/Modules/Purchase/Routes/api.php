<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\FastPurchaseController;
use Modules\Purchase\Http\Controllers\GoodsReceiptNoteController;
use Modules\Purchase\Http\Controllers\PurchaseContextController;
use Modules\Purchase\Http\Controllers\PurchaseDebitNoteController;
use Modules\Purchase\Http\Controllers\PurchaseEligibilityController;
use Modules\Purchase\Http\Controllers\PurchaseIntegrationController;
use Modules\Purchase\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Http\Controllers\PurchaseReturnController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:purchase',
];

Route::prefix('api/v1/purchase')->middleware($middleware)->name('api.v1.purchase.')->group(function (): void {
    Route::get('fast-purchases/context', [FastPurchaseController::class, 'context'])->name('fast-purchases.context');
    Route::post('fast-purchases/preview', [FastPurchaseController::class, 'preview'])->name('fast-purchases.preview');
    Route::post('fast-purchases', [FastPurchaseController::class, 'store'])->name('fast-purchases.store');

    Route::get('orders/create-context', [PurchaseContextController::class, 'orderCreateContext'])->name('orders.create-context');
    Route::get('suppliers/{supplier}/purchase-context', [PurchaseContextController::class, 'supplierContext'])->whereNumber('supplier')->name('suppliers.purchase-context');
    Route::get('items/{item}/purchase-context', [PurchaseContextController::class, 'itemContext'])->whereNumber('item')->name('items.purchase-context');
    Route::get('adjustments/catalogue', [PurchaseContextController::class, 'adjustmentCatalogue'])->name('adjustments.catalogue');
    Route::get('warehouses', [PurchaseContextController::class, 'warehouses'])->name('warehouses.index');
    Route::get('warehouses/{warehouse}/locations', [PurchaseContextController::class, 'warehouseLocations'])->whereNumber('warehouse')->name('warehouses.locations');

    Route::get('eligible/receivable-purchase-orders', [PurchaseEligibilityController::class, 'receivablePurchaseOrders'])->name('eligible.receivable-purchase-orders');
    Route::get('eligible/invoiceable-purchase-orders', [PurchaseEligibilityController::class, 'invoiceablePurchaseOrders'])->name('eligible.invoiceable-purchase-orders');
    Route::get('eligible/invoiceable-goods-receipts', [PurchaseEligibilityController::class, 'invoiceableGoodsReceipts'])->name('eligible.invoiceable-goods-receipts');
    Route::get('eligible/returnable-goods-receipts', [PurchaseEligibilityController::class, 'returnableGoodsReceipts'])->name('eligible.returnable-goods-receipts');
    Route::get('eligible/outstanding-supplier-invoices', [PurchaseEligibilityController::class, 'outstandingSupplierInvoices'])->name('eligible.outstanding-supplier-invoices');

    Route::get('orders', [PurchaseOrderController::class, 'index'])->name('orders.index');
    Route::post('orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [PurchaseOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::put('orders/{order}', [PurchaseOrderController::class, 'update'])->whereNumber('order')->name('orders.update');
    Route::delete('orders/{order}', [PurchaseOrderController::class, 'destroy'])->whereNumber('order')->name('orders.destroy');
    Route::patch('orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->whereNumber('order')->name('orders.submit');
    Route::patch('orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->whereNumber('order')->name('orders.approve');
    Route::patch('orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
    Route::patch('orders/{order}/close', [PurchaseOrderController::class, 'close'])->whereNumber('order')->name('orders.close');

    Route::get('orders/{order}/receivable-lines', [PurchaseEligibilityController::class, 'receivableLines'])->whereNumber('order')->name('orders.receivable-lines');
    Route::get('orders/{order}/invoiceable-lines', [PurchaseEligibilityController::class, 'invoiceableOrderLines'])->whereNumber('order')->name('orders.invoiceable-lines');

    Route::get('goods-receipts', [GoodsReceiptNoteController::class, 'index'])->name('goods-receipts.index');
    Route::post('goods-receipts', [GoodsReceiptNoteController::class, 'store'])->name('goods-receipts.store');
    Route::get('goods-receipts/{grn}', [GoodsReceiptNoteController::class, 'show'])->whereNumber('grn')->name('goods-receipts.show');
    Route::patch('goods-receipts/{grn}/post', [GoodsReceiptNoteController::class, 'post'])->whereNumber('grn')->name('goods-receipts.post');
    Route::patch('goods-receipts/{grn}/reverse', [GoodsReceiptNoteController::class, 'reverse'])->whereNumber('grn')->name('goods-receipts.reverse');
    Route::get('goods-receipts/{grn}/invoiceable-lines', [PurchaseEligibilityController::class, 'invoiceableGoodsReceiptLines'])->whereNumber('grn')->name('goods-receipts.invoiceable-lines');
    Route::get('goods-receipts/{grn}/returnable-lines', [PurchaseEligibilityController::class, 'returnableGoodsReceiptLines'])->whereNumber('grn')->name('goods-receipts.returnable-lines');

    Route::get('returns', [PurchaseReturnController::class, 'index'])->name('returns.index');
    Route::post('returns', [PurchaseReturnController::class, 'store'])->name('returns.store');
    Route::get('returns/{return}', [PurchaseReturnController::class, 'show'])->whereNumber('return')->name('returns.show');
    Route::patch('returns/{return}/approve', [PurchaseReturnController::class, 'approve'])->whereNumber('return')->name('returns.approve');
    Route::patch('returns/{return}/post', [PurchaseReturnController::class, 'post'])->whereNumber('return')->name('returns.post');
    Route::patch('returns/{return}/cancel', [PurchaseReturnController::class, 'cancel'])->whereNumber('return')->name('returns.cancel');

    Route::post('manual-supplier-returns', [PurchaseReturnController::class, 'manualSupplierReturn'])->name('manual-supplier-returns.store');
    Route::get('debit-notes', [PurchaseDebitNoteController::class, 'index'])->name('debit-notes.index');
    Route::post('debit-notes', [PurchaseDebitNoteController::class, 'store'])->name('debit-notes.store');
    Route::get('debit-notes/{debitNote}', [PurchaseDebitNoteController::class, 'show'])->whereNumber('debitNote')->name('debit-notes.show');
    Route::patch('debit-notes/{debitNote}/approve', [PurchaseDebitNoteController::class, 'approve'])->whereNumber('debitNote')->name('debit-notes.approve');
    Route::patch('debit-notes/{debitNote}/post', [PurchaseDebitNoteController::class, 'post'])->whereNumber('debitNote')->name('debit-notes.post');
    Route::post('debit-notes/{debitNote}/allocations', [PurchaseDebitNoteController::class, 'allocate'])->whereNumber('debitNote')->name('debit-notes.allocations.store');
    Route::post('invoices/preview', [PurchaseIntegrationController::class, 'previewInvoice'])->name('invoices.preview');
    Route::post('invoices', [PurchaseIntegrationController::class, 'createInvoice'])->name('invoices.store');
    Route::get('payments/context', [PurchaseIntegrationController::class, 'paymentContext'])->name('payments.context');
    Route::post('payments', [PurchaseIntegrationController::class, 'createPayment'])->name('payments.store');
    Route::post('payments/prepare', [PurchaseIntegrationController::class, 'preparePayment'])->name('payments.prepare');

    Route::get('suppliers/{supplier}/item-mappings', [PurchaseOrderController::class, 'supplierItemMappings'])->whereNumber('supplier')->name('suppliers.item-mappings');
});
