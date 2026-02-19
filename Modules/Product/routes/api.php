<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductCategoryController;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Http\Controllers\UnitController;

/*
|--------------------------------------------------------------------------
| Product API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:jwt'])->group(function () {

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/bundles', [ProductController::class, 'getBundleItems']);
    Route::post('products/{product}/bundles', [ProductController::class, 'addBundleItem']);
    Route::delete('products/{product}/bundles/{bundleItem}', [ProductController::class, 'removeBundleItem']);
    Route::get('products/{product}/composites', [ProductController::class, 'getCompositeParts']);
    Route::post('products/{product}/composites', [ProductController::class, 'addCompositePart']);
    Route::delete('products/{product}/composites/{compositePart}', [ProductController::class, 'removeCompositePart']);

    // Product Categories
    Route::apiResource('product-categories', ProductCategoryController::class);

    // Units
    Route::apiResource('units', UnitController::class);
});
