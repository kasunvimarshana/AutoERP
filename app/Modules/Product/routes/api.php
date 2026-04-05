<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ProductCategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;

Route::prefix('api')->group(function () {
    Route::get('product-categories/tree', [ProductCategoryController::class, 'tree']);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class);
});
