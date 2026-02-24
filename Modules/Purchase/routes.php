<?php
use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Controllers\VendorController;
use Modules\Purchase\Presentation\Controllers\PurchaseOrderController;
use Modules\Purchase\Presentation\Controllers\GoodsReceiptController;
use Modules\Purchase\Presentation\Controllers\PurchaseRequisitionController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('purchase/vendors', VendorController::class);
    Route::apiResource('purchase/orders', PurchaseOrderController::class);
    Route::post('purchase/orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
    Route::post('purchase/orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
    Route::apiResource('purchase/receipts', GoodsReceiptController::class)->only(['index', 'show', 'store']);
    Route::apiResource('purchase/requisitions', PurchaseRequisitionController::class);
    Route::post('purchase/requisitions/{id}/approve', [PurchaseRequisitionController::class, 'approve']);
    Route::post('purchase/requisitions/{id}/reject', [PurchaseRequisitionController::class, 'reject']);
    Route::post('purchase/requisitions/{id}/convert-to-po', [PurchaseRequisitionController::class, 'convertToPo']);
});
