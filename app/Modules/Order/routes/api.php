<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Order\Infrastructure\Http\Controllers\OrderLineController;
use Modules\Order\Infrastructure\Http\Controllers\PurchaseOrderController;
use Modules\Order\Infrastructure\Http\Controllers\SalesOrderController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::post('purchase-orders/{id}/confirm', [PurchaseOrderController::class, 'confirm']);
    Route::post('purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
    Route::apiResource('sales-orders', SalesOrderController::class);

    Route::apiResource('order-lines', OrderLineController::class);
});
