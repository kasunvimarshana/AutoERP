<?php

use Illuminate\Support\Facades\Route;
use Modules\Procurement\Http\Controllers\Api\GoodsReceiptController;
use Modules\Procurement\Http\Controllers\Api\PurchaseOrderController;
use Modules\Procurement\Http\Controllers\Api\PurchaseOrderLineController;
use Modules\Procurement\Http\Controllers\Api\PurchaseRequisitionController;
use Modules\Procurement\Http\Controllers\Api\PurchaseRequisitionLineController;
use Modules\Procurement\Http\Controllers\Api\SupplierController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Procurement module.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Supplier routes
    Route::get('suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
    Route::get('suppliers/by-rating', [SupplierController::class, 'byRating'])->name('suppliers.by-rating');
    Route::post('suppliers/{id}/rate', [SupplierController::class, 'rate'])->name('suppliers.rate');
    Route::get('suppliers/{id}/evaluate', [SupplierController::class, 'evaluate'])->name('suppliers.evaluate');
    Route::apiResource('suppliers', SupplierController::class);

    // Purchase Requisition routes
    Route::get('purchase-requisitions/search', [PurchaseRequisitionController::class, 'search'])->name('purchase-requisitions.search');
    Route::get('purchase-requisitions/by-status', [PurchaseRequisitionController::class, 'byStatus'])->name('purchase-requisitions.by-status');
    Route::post('purchase-requisitions/{id}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve');
    Route::post('purchase-requisitions/{id}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject');
    Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);

    // Purchase Requisition Line routes
    Route::get('purchase-requisition-lines/by-requisition/{requisitionId}', [PurchaseRequisitionLineController::class, 'byRequisition'])->name('purchase-requisition-lines.by-requisition');
    Route::apiResource('purchase-requisition-lines', PurchaseRequisitionLineController::class);

    // Purchase Order routes
    Route::get('purchase-orders/search', [PurchaseOrderController::class, 'search'])->name('purchase-orders.search');
    Route::get('purchase-orders/by-status', [PurchaseOrderController::class, 'byStatus'])->name('purchase-orders.by-status');
    Route::post('purchase-orders/{id}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{id}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchase-orders.confirm');
    Route::post('purchase-orders/{id}/close', [PurchaseOrderController::class, 'close'])->name('purchase-orders.close');
    Route::post('purchase-orders/from-requisition', [PurchaseOrderController::class, 'createFromRequisition'])->name('purchase-orders.from-requisition');
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    // Purchase Order Line routes
    Route::get('purchase-order-lines/by-order/{orderId}', [PurchaseOrderLineController::class, 'byOrder'])->name('purchase-order-lines.by-order');
    Route::apiResource('purchase-order-lines', PurchaseOrderLineController::class);

    // Goods Receipt routes
    Route::get('goods-receipts/search', [GoodsReceiptController::class, 'search'])->name('goods-receipts.search');
    Route::get('goods-receipts/by-purchase-order/{purchaseOrderId}', [GoodsReceiptController::class, 'byPurchaseOrder'])->name('goods-receipts.by-purchase-order');
    Route::post('goods-receipts/{id}/confirm', [GoodsReceiptController::class, 'confirm'])->name('goods-receipts.confirm');
    Route::post('goods-receipts/{id}/match', [GoodsReceiptController::class, 'match'])->name('goods-receipts.match');
    Route::apiResource('goods-receipts', GoodsReceiptController::class);
});
