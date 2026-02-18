<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Api\CustomerController;
use Modules\Sales\Http\Controllers\Api\LeadController;
use Modules\Sales\Http\Controllers\Api\SalesOrderController;
use Modules\Sales\Http\Controllers\Api\SalesOrderLineController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Sales module.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Customer search must be before resource routes
    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');

    // Customer routes
    Route::apiResource('customers', CustomerController::class);

    // Lead routes
    Route::get('leads/search', [LeadController::class, 'search'])->name('leads.search');
    Route::post('leads/{id}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::apiResource('leads', LeadController::class);

    // Sales Order routes
    Route::get('sales-orders/search', [SalesOrderController::class, 'search'])->name('sales-orders.search');
    Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
    Route::post('sales-orders/{id}/calculate-totals', [SalesOrderController::class, 'calculateTotals'])->name('sales-orders.calculate-totals');
    Route::apiResource('sales-orders', SalesOrderController::class);

    // Sales Order Line routes
    Route::get('sales-order-lines/by-order/{salesOrderId}', [SalesOrderLineController::class, 'getByOrder'])->name('sales-order-lines.by-order');
    Route::apiResource('sales-order-lines', SalesOrderLineController::class);
});
