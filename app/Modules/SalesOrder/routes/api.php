<?php
use Illuminate\Support\Facades\Route;
use Modules\SalesOrder\Infrastructure\Http\Controllers\SalesOrderController;
Route::prefix('api')->group(function () {
    Route::get('sales-orders', [SalesOrderController::class, 'index']);
    Route::post('sales-orders', [SalesOrderController::class, 'store']);
    Route::get('sales-orders/{id}', [SalesOrderController::class, 'show']);
    Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('sales-orders/{id}/start-picking', [SalesOrderController::class, 'startPicking']);
    Route::post('sales-orders/{id}/start-packing', [SalesOrderController::class, 'startPacking']);
    Route::delete('sales-orders/{id}', [SalesOrderController::class, 'destroy']);
});
