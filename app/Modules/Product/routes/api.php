<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ProductComboItemController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductImageController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariationController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('products', ProductController::class);

    Route::get('products/{product}/images', [ProductImageController::class, 'index']);
    Route::post('products/{product}/images', [ProductImageController::class, 'store']);
    Route::post('products/{product}/images/bulk', [ProductImageController::class, 'storeMany']);
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);

    // Variable product variations
    Route::get('products/{product}/variations', [ProductVariationController::class, 'index']);
    Route::post('products/{product}/variations', [ProductVariationController::class, 'store']);
    Route::put('products/{product}/variations/{variation}', [ProductVariationController::class, 'update']);
    Route::delete('products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy']);

    // Combo product items
    Route::get('products/{product}/combo-items', [ProductComboItemController::class, 'index']);
    Route::post('products/{product}/combo-items', [ProductComboItemController::class, 'store']);
    Route::put('products/{product}/combo-items/{item}', [ProductComboItemController::class, 'update']);
    Route::delete('products/{product}/combo-items/{item}', [ProductComboItemController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/product-images/{uuid}', [ProductImageController::class, 'serve'])->middleware('auth:api');
