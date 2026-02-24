<?php

use Illuminate\Support\Facades\Route;
use Modules\ECommerce\Presentation\Controllers\ECommerceOrderController;
use Modules\ECommerce\Presentation\Controllers\ProductListingController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('ecommerce/catalog', [ProductListingController::class, 'catalog']);
    Route::apiResource('ecommerce/products', ProductListingController::class);
    Route::get('ecommerce/orders', [ECommerceOrderController::class, 'index']);
    Route::post('ecommerce/orders', [ECommerceOrderController::class, 'store']);
    Route::get('ecommerce/orders/{id}', [ECommerceOrderController::class, 'show']);
    Route::delete('ecommerce/orders/{id}', [ECommerceOrderController::class, 'destroy']);
    Route::post('ecommerce/orders/{id}/confirm', [ECommerceOrderController::class, 'confirm']);
});
