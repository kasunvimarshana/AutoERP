<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Procurement\Interfaces\Http\Controllers\ProcurementController;

/*
|--------------------------------------------------------------------------
| Procurement Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('procurement.')->group(function (): void {
    Route::post('procurement/orders', [ProcurementController::class, 'createPurchaseOrder'])->name('orders.store');
    Route::get('procurement/orders', [ProcurementController::class, 'listOrders'])->name('orders.index');
    Route::get('procurement/orders/{id}', [ProcurementController::class, 'showPurchaseOrder'])->name('orders.show');
    Route::put('procurement/orders/{id}', [ProcurementController::class, 'updatePurchaseOrder'])->name('orders.update');
    Route::post('procurement/orders/{id}/receive', [ProcurementController::class, 'receiveGoods'])->name('orders.receive');
    Route::get('procurement/orders/{id}/three-way-match', [ProcurementController::class, 'threeWayMatch'])->name('orders.three-way-match');

    // Vendor endpoints
    Route::get('procurement/vendors', [ProcurementController::class, 'listVendors'])->name('vendors.index');
    Route::post('procurement/vendors', [ProcurementController::class, 'createVendor'])->name('vendors.store');
    Route::get('procurement/vendors/{id}', [ProcurementController::class, 'showVendor'])->name('vendors.show');
    Route::put('procurement/vendors/{id}', [ProcurementController::class, 'updateVendor'])->name('vendors.update');

    // Vendor bill endpoints
    Route::get('procurement/vendor-bills', [ProcurementController::class, 'listVendorBills'])->name('vendor-bills.index');
    Route::post('procurement/vendor-bills', [ProcurementController::class, 'createVendorBill'])->name('vendor-bills.store');
    Route::get('procurement/vendor-bills/{id}', [ProcurementController::class, 'showVendorBill'])->name('vendor-bills.show');
});
