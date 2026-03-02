<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Interfaces\Http\Controllers\ProductAttributeController;
use Modules\Product\Interfaces\Http\Controllers\ProductController;
use Modules\Product\Interfaces\Http\Controllers\ProductImageController;
use Modules\Product\Interfaces\Http\Controllers\UomConversionController;

Route::prefix('api/v1')->group(function (): void {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // UOM conversion management per product
    Route::get('/products/{productId}/uom-conversions', [UomConversionController::class, 'index']);
    Route::post('/products/{productId}/uom-conversions', [UomConversionController::class, 'store']);
    Route::get('/products/{productId}/uom-conversions/convert', [UomConversionController::class, 'convert']);

    // Product image management (multiple images per product)
    Route::get('/products/{productId}/images', [ProductImageController::class, 'index']);
    Route::post('/products/{productId}/images', [ProductImageController::class, 'store']);
    Route::post('/products/{productId}/images/upload', [ProductImageController::class, 'upload']);
    Route::delete('/products/{productId}/images/{imageId}', [ProductImageController::class, 'destroy']);

    // Product attribute management (dynamic, extensible attributes per product)
    Route::get('/products/{productId}/attributes', [ProductAttributeController::class, 'index']);
    Route::post('/products/{productId}/attributes', [ProductAttributeController::class, 'store']);
    Route::delete('/products/{productId}/attributes/{attributeId}', [ProductAttributeController::class, 'destroy']);
});
