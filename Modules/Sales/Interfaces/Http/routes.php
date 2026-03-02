<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Interfaces\Http\Controllers\SalesOrderController;

Route::prefix('api/v1')->group(function (): void {
    Route::get('/sales/orders', [SalesOrderController::class, 'index']);
    Route::post('/sales/orders', [SalesOrderController::class, 'store']);
    Route::get('/sales/orders/{id}', [SalesOrderController::class, 'show']);
    Route::post('/sales/orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('/sales/orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
    Route::delete('/sales/orders/{id}', [SalesOrderController::class, 'destroy']);
});
