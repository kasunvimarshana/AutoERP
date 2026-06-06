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
    Route::post('orders/{order}/approve', [PurchaseController::class, 'approve'])->whereNumber('order')->name('orders.approve');
    Route::patch('orders/{order}/approve', [PurchaseController::class, 'approve'])->whereNumber('order')->name('orders.approve.patch');
    Route::post('orders/{order}/cancel', [PurchaseController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
    Route::patch('orders/{order}/cancel', [PurchaseController::class, 'cancel'])->whereNumber('order')->name('orders.cancel.patch');
    Route::post('orders/{order}/close', [PurchaseController::class, 'close'])->whereNumber('order')->name('orders.close');
    Route::patch('orders/{order}/close', [PurchaseController::class, 'close'])->whereNumber('order')->name('orders.close.patch');
    Route::post('goods-receipts', [PurchaseController::class, 'createGrn'])->name('goods-receipts.store');
    Route::post('goods-receipts/{grn}/post', [PurchaseController::class, 'postGrn'])->whereNumber('grn')->name('goods-receipts.post');
    Route::post('returns', [PurchaseController::class, 'createReturn'])->name('returns.store');
    Route::post('returns/{return}/post', [PurchaseController::class, 'postReturn'])->whereNumber('return')->name('returns.post');
    Route::post('invoices/preview', [PurchaseController::class, 'previewInvoice'])->name('invoices.preview');
    Route::post('invoices', [PurchaseController::class, 'createInvoice'])->name('invoices.store');
    Route::post('payments/prepare', [PurchaseController::class, 'preparePayment'])->name('payments.prepare');
});
