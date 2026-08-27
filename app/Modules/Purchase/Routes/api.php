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
use Modules\Purchase\Services\PurchaseAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:purchase',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/purchase')->middleware($middleware)->name('api.v1.purchase.')->group(function () use ($requires): void {
    Route::get('fast-purchases/context', [FastPurchaseController::class, 'context'])->middleware($requires(PurchaseAuthorizationService::FAST_PURCHASE_VIEW))->name('fast-purchases.context');
    Route::post('fast-purchases/preview', [FastPurchaseController::class, 'preview'])->middleware($requires(PurchaseAuthorizationService::FAST_PURCHASE_VIEW))->name('fast-purchases.preview');
    Route::post('fast-purchases', [FastPurchaseController::class, 'store'])->middleware($requires(PurchaseAuthorizationService::FAST_PURCHASE_EXECUTE))->name('fast-purchases.store');

    Route::get('orders/create-context', [PurchaseContextController::class, 'orderCreateContext'])->middleware($requires(PurchaseAuthorizationService::ORDERS_CREATE))->name('orders.create-context');
    Route::get('suppliers/{supplier}/purchase-context', [PurchaseContextController::class, 'supplierContext'])->whereNumber('supplier')->name('suppliers.purchase-context');
    Route::get('items/{item}/purchase-context', [PurchaseContextController::class, 'itemContext'])->whereNumber('item')->name('items.purchase-context');
    Route::get('adjustments/catalogue', [PurchaseContextController::class, 'adjustmentCatalogue'])->name('adjustments.catalogue');
    Route::get('warehouses', [PurchaseContextController::class, 'warehouses'])->name('warehouses.index');
    Route::get('warehouses/{warehouse}/locations', [PurchaseContextController::class, 'warehouseLocations'])->whereNumber('warehouse')->name('warehouses.locations');

    Route::get('eligible/receivable-purchase-orders', [PurchaseEligibilityController::class, 'receivablePurchaseOrders'])->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW))->name('eligible.receivable-purchase-orders');
    Route::get('eligible/invoiceable-purchase-orders', [PurchaseEligibilityController::class, 'invoiceablePurchaseOrders'])->middleware($requires(PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW))->name('eligible.invoiceable-purchase-orders');
    Route::get('eligible/invoiceable-goods-receipts', [PurchaseEligibilityController::class, 'invoiceableGoodsReceipts'])->middleware($requires(PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW))->name('eligible.invoiceable-goods-receipts');
    Route::get('eligible/returnable-goods-receipts', [PurchaseEligibilityController::class, 'returnableGoodsReceipts'])->middleware($requires(PurchaseAuthorizationService::RETURNS_VIEW))->name('eligible.returnable-goods-receipts');
    Route::get('eligible/outstanding-supplier-invoices', [PurchaseEligibilityController::class, 'outstandingSupplierInvoices'])->middleware($requires(PurchaseAuthorizationService::PAYMENTS_VIEW))->name('eligible.outstanding-supplier-invoices');

    Route::get('orders', [PurchaseOrderController::class, 'index'])->middleware($requires(PurchaseAuthorizationService::ORDERS_VIEW))->name('orders.index');
    Route::post('orders', [PurchaseOrderController::class, 'store'])->middleware($requires(PurchaseAuthorizationService::ORDERS_CREATE))->name('orders.store');
    Route::get('orders/{order}', [PurchaseOrderController::class, 'show'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_VIEW))->name('orders.show');
    Route::get('orders/{order}/pdf', [PurchaseOrderController::class, 'pdf'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_VIEW))->name('orders.pdf');
    Route::put('orders/{order}', [PurchaseOrderController::class, 'update'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_UPDATE))->name('orders.update');
    Route::delete('orders/{order}', [PurchaseOrderController::class, 'destroy'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_DELETE))->name('orders.destroy');
    Route::patch('orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_SUBMIT))->name('orders.submit');
    Route::patch('orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_APPROVE))->name('orders.approve');
    Route::patch('orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_CANCEL))->name('orders.cancel');
    Route::patch('orders/{order}/close', [PurchaseOrderController::class, 'close'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::ORDERS_CLOSE))->name('orders.close');

    Route::get('orders/{order}/receivable-lines', [PurchaseEligibilityController::class, 'receivableLines'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW))->name('orders.receivable-lines');
    Route::get('orders/{order}/invoiceable-lines', [PurchaseEligibilityController::class, 'invoiceableOrderLines'])->whereNumber('order')->middleware($requires(PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW))->name('orders.invoiceable-lines');

    Route::get('goods-receipts', [GoodsReceiptNoteController::class, 'index'])->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW))->name('goods-receipts.index');
    Route::post('goods-receipts', [GoodsReceiptNoteController::class, 'store'])->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_CREATE))->name('goods-receipts.store');
    Route::get('goods-receipts/{grn}', [GoodsReceiptNoteController::class, 'show'])->whereNumber('grn')->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW))->name('goods-receipts.show');
    Route::patch('goods-receipts/{grn}/post', [GoodsReceiptNoteController::class, 'post'])->whereNumber('grn')->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_POST))->name('goods-receipts.post');
    Route::patch('goods-receipts/{grn}/reverse', [GoodsReceiptNoteController::class, 'reverse'])->whereNumber('grn')->middleware($requires(PurchaseAuthorizationService::GOODS_RECEIPTS_REVERSE))->name('goods-receipts.reverse');
    Route::get('goods-receipts/{grn}/invoiceable-lines', [PurchaseEligibilityController::class, 'invoiceableGoodsReceiptLines'])->whereNumber('grn')->middleware($requires(PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW))->name('goods-receipts.invoiceable-lines');
    Route::get('goods-receipts/{grn}/returnable-lines', [PurchaseEligibilityController::class, 'returnableGoodsReceiptLines'])->whereNumber('grn')->middleware($requires(PurchaseAuthorizationService::RETURNS_VIEW))->name('goods-receipts.returnable-lines');

    Route::get('returns', [PurchaseReturnController::class, 'index'])->middleware($requires(PurchaseAuthorizationService::RETURNS_VIEW))->name('returns.index');
    Route::post('returns', [PurchaseReturnController::class, 'store'])->middleware($requires(PurchaseAuthorizationService::RETURNS_CREATE))->name('returns.store');
    Route::get('returns/{return}', [PurchaseReturnController::class, 'show'])->whereNumber('return')->middleware($requires(PurchaseAuthorizationService::RETURNS_VIEW))->name('returns.show');
    Route::patch('returns/{return}/approve', [PurchaseReturnController::class, 'approve'])->whereNumber('return')->middleware($requires(PurchaseAuthorizationService::RETURNS_APPROVE))->name('returns.approve');
    Route::patch('returns/{return}/post', [PurchaseReturnController::class, 'post'])->whereNumber('return')->middleware($requires(PurchaseAuthorizationService::RETURNS_POST))->name('returns.post');
    Route::patch('returns/{return}/cancel', [PurchaseReturnController::class, 'cancel'])->whereNumber('return')->middleware($requires(PurchaseAuthorizationService::RETURNS_CANCEL))->name('returns.cancel');

    Route::post('manual-supplier-returns', [PurchaseReturnController::class, 'manualSupplierReturn'])->middleware($requires(PurchaseAuthorizationService::RETURNS_CREATE_MANUAL))->name('manual-supplier-returns.store');
    Route::get('debit-notes', [PurchaseDebitNoteController::class, 'index'])->middleware($requires(PurchaseAuthorizationService::DEBIT_NOTES_VIEW))->name('debit-notes.index');
    Route::post('debit-notes', [PurchaseDebitNoteController::class, 'store'])->middleware($requires(PurchaseAuthorizationService::DEBIT_NOTES_CREATE))->name('debit-notes.store');
    Route::get('debit-notes/{debitNote}', [PurchaseDebitNoteController::class, 'show'])->whereNumber('debitNote')->middleware($requires(PurchaseAuthorizationService::DEBIT_NOTES_VIEW))->name('debit-notes.show');
    Route::patch('debit-notes/{debitNote}/approve', [PurchaseDebitNoteController::class, 'approve'])->whereNumber('debitNote')->middleware($requires(PurchaseAuthorizationService::DEBIT_NOTES_APPROVE))->name('debit-notes.approve');
    Route::patch('debit-notes/{debitNote}/post', [PurchaseDebitNoteController::class, 'post'])->whereNumber('debitNote')->middleware($requires(PurchaseAuthorizationService::DEBIT_NOTES_POST))->name('debit-notes.post');
    Route::post('debit-notes/{debitNote}/allocations', [PurchaseDebitNoteController::class, 'allocate'])->whereNumber('debitNote')->middleware($requires(PurchaseAuthorizationService::DEBIT_NOTES_ALLOCATE))->name('debit-notes.allocations.store');
    Route::post('invoices/preview', [PurchaseIntegrationController::class, 'previewInvoice'])->middleware($requires(PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW))->name('invoices.preview');
    Route::post('invoices', [PurchaseIntegrationController::class, 'createInvoice'])->middleware($requires(PurchaseAuthorizationService::SUPPLIER_INVOICES_CREATE))->name('invoices.store');
    Route::get('payments/context', [PurchaseIntegrationController::class, 'paymentContext'])->middleware($requires(PurchaseAuthorizationService::PAYMENTS_VIEW))->name('payments.context');
    Route::post('payments', [PurchaseIntegrationController::class, 'createPayment'])->middleware($requires(PurchaseAuthorizationService::PAYMENTS_EXECUTE))->name('payments.store');
    Route::post('payments/prepare', [PurchaseIntegrationController::class, 'preparePayment'])->middleware($requires(PurchaseAuthorizationService::PAYMENTS_VIEW))->name('payments.prepare');

    Route::get('suppliers/{supplier}/item-mappings', [PurchaseOrderController::class, 'supplierItemMappings'])->whereNumber('supplier')->middleware($requires(PurchaseAuthorizationService::ORDERS_VIEW))->name('suppliers.item-mappings');
});
