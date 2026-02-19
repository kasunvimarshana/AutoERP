<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductCategoryController;
use Modules\Product\Http\Controllers\ProductController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 */

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Products
    Route::apiResource('products', ProductController::class)->names('product');

    // Product Categories
    Route::get('product-categories/tree', [ProductCategoryController::class, 'tree'])->name('product-categories.tree');
    Route::apiResource('product-categories', ProductCategoryController::class)->names('product-categories');
});
