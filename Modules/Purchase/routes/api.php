<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\BillController;
use Modules\Purchase\Http\Controllers\GoodsReceiptController;
use Modules\Purchase\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Http\Controllers\VendorController;

/*
|--------------------------------------------------------------------------
| Purchase Module API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with '/api/purchase' and protected by 'api' middleware.
| Tenant context is automatically applied via TenantContext middleware.
|
*/

Route::middleware(['api', 'jwt.auth'])->prefix('api/v1/purchase')->name('purchase.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Vendor Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('index');
        Route::post('/', [VendorController::class, 'store'])->name('store');
        Route::get('/{id}', [VendorController::class, 'show'])->name('show');
        Route::put('/{id}', [VendorController::class, 'update'])->name('update');
        Route::delete('/{id}', [VendorController::class, 'destroy'])->name('destroy');

        // Status management
        Route::post('/{id}/activate', [VendorController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [VendorController::class, 'deactivate'])->name('deactivate');
        Route::post('/{id}/block', [VendorController::class, 'block'])->name('block');
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase Order Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::put('/{id}', [PurchaseOrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy'])->name('destroy');

        // Workflow actions
        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('approve');
        Route::post('/{id}/send', [PurchaseOrderController::class, 'send'])->name('send');
        Route::post('/{id}/confirm', [PurchaseOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Goods Receipt Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('goods-receipts')->name('goods-receipts.')->group(function () {
        Route::get('/', [GoodsReceiptController::class, 'index'])->name('index');
        Route::post('/', [GoodsReceiptController::class, 'store'])->name('store');
        Route::get('/{id}', [GoodsReceiptController::class, 'show'])->name('show');
        Route::put('/{id}', [GoodsReceiptController::class, 'update'])->name('update');
        Route::delete('/{id}', [GoodsReceiptController::class, 'destroy'])->name('destroy');

        // Workflow actions
        Route::post('/{id}/confirm', [GoodsReceiptController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/post-to-inventory', [GoodsReceiptController::class, 'postToInventory'])->name('post-to-inventory');
        Route::post('/{id}/cancel', [GoodsReceiptController::class, 'cancel'])->name('cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Bill Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('bills')->name('bills.')->group(function () {
        Route::get('/', [BillController::class, 'index'])->name('index');
        Route::post('/', [BillController::class, 'store'])->name('store');
        Route::get('/{id}', [BillController::class, 'show'])->name('show');
        Route::put('/{id}', [BillController::class, 'update'])->name('update');
        Route::delete('/{id}', [BillController::class, 'destroy'])->name('destroy');

        // Workflow actions
        Route::post('/{id}/send', [BillController::class, 'send'])->name('send');
        Route::post('/{id}/record-payment', [BillController::class, 'recordPayment'])->name('record-payment');
        Route::post('/{id}/cancel', [BillController::class, 'cancel'])->name('cancel');
    });
});
