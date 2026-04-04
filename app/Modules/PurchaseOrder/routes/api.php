<?php
use Illuminate\Support\Facades\Route;
use Modules\PurchaseOrder\Infrastructure\Http\Controllers\PurchaseOrderController;

Route::prefix('purchase-orders')->group(function () {
    Route::get('/',               [PurchaseOrderController::class, 'index']);
    Route::post('/',              [PurchaseOrderController::class, 'store']);
    Route::get('/{id}',           [PurchaseOrderController::class, 'show']);
    Route::patch('/{id}',         [PurchaseOrderController::class, 'update']);
    Route::delete('/{id}',        [PurchaseOrderController::class, 'destroy']);
    Route::post('/{id}/approve',  [PurchaseOrderController::class, 'approve']);
    Route::post('/{id}/cancel',   [PurchaseOrderController::class, 'cancel']);
});
