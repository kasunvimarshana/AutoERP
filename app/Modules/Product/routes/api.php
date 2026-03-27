<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductImageController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('products', ProductController::class);

    Route::get('products/{product}/images', [ProductImageController::class, 'index']);
    Route::post('products/{product}/images', [ProductImageController::class, 'store']);
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/product-images/{uuid}', [ProductImageController::class, 'serve'])->middleware('auth:api');
