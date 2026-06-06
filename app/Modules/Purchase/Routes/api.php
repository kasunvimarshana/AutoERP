<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\PurchaseController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/purchase')->middleware($middleware)->name('api.v1.purchase.')->group(function (): void {
    Route::get('orders', [PurchaseController::class, 'index'])->name('orders.index');
    Route::post('orders', [PurchaseController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [PurchaseController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::put('orders/{order}', [PurchaseController::class, 'update'])->whereNumber('order')->name('orders.update');
    Route::delete('orders/{order}', [PurchaseController::class, 'destroy'])->whereNumber('order')->name('orders.destroy');
    Route::patch('orders/{order}/submit', [PurchaseController::class, 'submit'])->whereNumber('order')->name('orders.submit');
    Route::patch('orders/{order}/approve', [PurchaseController::class, 'approve'])->whereNumber('order')->name('orders.approve');
    Route::patch('orders/{order}/cancel', [PurchaseController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
    Route::patch('orders/{order}/close', [PurchaseController::class, 'close'])->whereNumber('order')->name('orders.close');

    Route::get('orders/{order}/receivable-lines', [PurchaseController::class, 'receivableLines'])->whereNumber('order')->name('orders.receivable-lines');
    Route::get('orders/{order}/invoiceable-lines', [PurchaseController::class, 'invoiceableLines'])->whereNumber('order')->name('orders.invoiceable-lines');

    Route::get('goods-receipts', [PurchaseController::class, 'grnIndex'])->name('goods-receipts.index');
    Route::post('goods-receipts', [PurchaseController::class, 'createGrn'])->name('goods-receipts.store');
    Route::get('goods-receipts/{grn}', [PurchaseController::class, 'showGrn'])->whereNumber('grn')->name('goods-receipts.show');
    Route::patch('goods-receipts/{grn}/post', [PurchaseController::class, 'postGrn'])->whereNumber('grn')->name('goods-receipts.post');
    Route::patch('goods-receipts/{grn}/reverse', [PurchaseController::class, 'reverseGrn'])->whereNumber('grn')->name('goods-receipts.reverse');
    Route::get('goods-receipts/{grn}/returnable-lines', [PurchaseController::class, 'returnableLines'])->whereNumber('grn')->name('goods-receipts.returnable-lines');

    Route::get('returns', [PurchaseController::class, 'returnIndex'])->name('returns.index');
    Route::post('returns', [PurchaseController::class, 'createReturn'])->name('returns.store');
    Route::get('returns/{return}', [PurchaseController::class, 'showReturn'])->whereNumber('return')->name('returns.show');
    Route::patch('returns/{return}/approve', [PurchaseController::class, 'approveReturn'])->whereNumber('return')->name('returns.approve');
    Route::patch('returns/{return}/post', [PurchaseController::class, 'postReturn'])->whereNumber('return')->name('returns.post');
    Route::patch('returns/{return}/cancel', [PurchaseController::class, 'cancelReturn'])->whereNumber('return')->name('returns.cancel');

    Route::post('manual-supplier-returns', [PurchaseController::class, 'createManualSupplierReturn'])->name('manual-supplier-returns.store');
    Route::get('debit-notes', [PurchaseController::class, 'debitNoteIndex'])->name('debit-notes.index');
    Route::post('debit-notes', [PurchaseController::class, 'createDebitNote'])->name('debit-notes.store');
    Route::get('debit-notes/{debitNote}', [PurchaseController::class, 'showDebitNote'])->whereNumber('debitNote')->name('debit-notes.show');
    Route::post('inventory-adjustment-requests', [PurchaseController::class, 'createInventoryAdjustmentRequest'])->name('inventory-adjustment-requests.store');

    Route::post('invoices/preview', [PurchaseController::class, 'previewInvoice'])->name('invoices.preview');
    Route::post('invoices', [PurchaseController::class, 'createInvoice'])->name('invoices.store');
    Route::post('payments/prepare', [PurchaseController::class, 'preparePayment'])->name('payments.prepare');

    Route::get('suppliers/{supplier}/item-mappings', [PurchaseController::class, 'supplierItemMappings'])->whereNumber('supplier')->name('suppliers.item-mappings');
});
