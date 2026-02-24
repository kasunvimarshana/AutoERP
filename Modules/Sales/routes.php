<?php
use Illuminate\Support\Facades\Route;
use Modules\Sales\Presentation\Controllers\CustomerController;
use Modules\Sales\Presentation\Controllers\PriceListController;
use Modules\Sales\Presentation\Controllers\QuotationController;
use Modules\Sales\Presentation\Controllers\SalesOrderController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('sales/customers', CustomerController::class);
    Route::apiResource('sales/quotations', QuotationController::class);
    Route::post('sales/quotations/{id}/convert', [QuotationController::class, 'convertToOrder']);
    Route::apiResource('sales/orders', SalesOrderController::class);
    Route::post('sales/orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('sales/orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
    Route::post('sales/orders/{id}/ship', [SalesOrderController::class, 'ship']);
    Route::apiResource('sales/price-lists', PriceListController::class);
    Route::post('sales/price-lists/{id}/items', [PriceListController::class, 'addItem']);
    Route::post('sales/price-lists/{id}/resolve-price', [PriceListController::class, 'resolvePrice']);
});
