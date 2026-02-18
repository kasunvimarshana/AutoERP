<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchasing\Http\Controllers\GoodsReceiptController;
use Modules\Purchasing\Http\Controllers\PurchaseOrderController;
use Modules\Purchasing\Http\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| Purchasing API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Purchasing module.
| These routes are loaded by the PurchasingServiceProvider.
|
*/

// Supplier Management
Route::apiResource('suppliers', SupplierController::class);
Route::post('suppliers/{id}/activate', [SupplierController::class, 'activate']);
Route::post('suppliers/{id}/suspend', [SupplierController::class, 'suspend']);

// Purchase Orders
Route::apiResource('purchase-orders', PurchaseOrderController::class);
Route::post('purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
Route::post('purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit']);
Route::post('purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);

// Goods Receipts
Route::apiResource('goods-receipts', GoodsReceiptController::class);
Route::post('goods-receipts/{id}/receive', [GoodsReceiptController::class, 'markAsReceived']);
Route::post('goods-receipts/{id}/inspect', [GoodsReceiptController::class, 'inspect']);
Route::post('goods-receipts/{id}/accept', [GoodsReceiptController::class, 'accept']);
Route::post('goods-receipts/{id}/reject', [GoodsReceiptController::class, 'reject']);
Route::get('purchase-orders/{purchase_order_id}/goods-receipts', [GoodsReceiptController::class, 'getByPurchaseOrder']);
