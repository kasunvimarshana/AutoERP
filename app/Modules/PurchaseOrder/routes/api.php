<?php

use Illuminate\Support\Facades\Route;
use Modules\PurchaseOrder\Infrastructure\Http\Controllers\PurchaseOrderController;
use Modules\PurchaseOrder\Infrastructure\Http\Controllers\PurchaseOrderLineController;

Route::apiResource('purchase-orders', PurchaseOrderController::class);
Route::post('purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit']);
Route::post('purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
Route::post('purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
Route::apiResource('purchase-order-lines', PurchaseOrderLineController::class);
