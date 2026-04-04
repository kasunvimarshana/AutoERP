<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesOrder\Infrastructure\Http\Controllers\SalesOrderController;

Route::prefix('sales-orders')->group(function () {
    Route::get('/',                       [SalesOrderController::class, 'index']);
    Route::post('/',                      [SalesOrderController::class, 'store']);
    Route::get('/{id}',                   [SalesOrderController::class, 'show']);
    Route::patch('/{id}',                 [SalesOrderController::class, 'update']);
    Route::delete('/{id}',                [SalesOrderController::class, 'destroy']);
    Route::post('/{id}/confirm',          [SalesOrderController::class, 'confirm']);
    Route::post('/{id}/cancel',           [SalesOrderController::class, 'cancel']);
    Route::post('/{id}/start-picking',    [SalesOrderController::class, 'startPicking']);
    Route::post('/{id}/start-packing',    [SalesOrderController::class, 'startPacking']);
});
