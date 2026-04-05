<?php
use Illuminate\Support\Facades\Route;
use Modules\PurchaseOrder\Infrastructure\Http\Controllers\PurchaseOrderController;
Route::prefix('api')->group(function () {
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::get('purchase-orders/{id}', [PurchaseOrderController::class, 'show']);
    Route::post('purchase-orders/{id}/confirm', [PurchaseOrderController::class, 'confirm']);
    Route::delete('purchase-orders/{id}', [PurchaseOrderController::class, 'destroy']);
});
