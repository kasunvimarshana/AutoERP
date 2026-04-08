<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesOrder\Infrastructure\Http\Controllers\SalesOrderController;
use Modules\SalesOrder\Infrastructure\Http\Controllers\SalesOrderLineController;

Route::apiResource('sales-orders', SalesOrderController::class);
Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
Route::post('sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
Route::post('sales-orders/{id}/start-picking', [SalesOrderController::class, 'startPicking']);
Route::post('sales-orders/{id}/start-packing', [SalesOrderController::class, 'startPacking']);
Route::post('sales-orders/{id}/ship', [SalesOrderController::class, 'ship']);
Route::post('sales-orders/{id}/deliver', [SalesOrderController::class, 'deliver']);
Route::apiResource('sales-order-lines', SalesOrderLineController::class);
