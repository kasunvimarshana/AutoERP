<?php
use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ProductCategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariantController;

Route::prefix('product-categories')->group(function () {
    Route::get('/tree',    [ProductCategoryController::class, 'tree']);
    Route::get('/',        [ProductCategoryController::class, 'index']);
    Route::post('/',       [ProductCategoryController::class, 'store']);
    Route::get('/{id}',    [ProductCategoryController::class, 'show']);
    Route::patch('/{id}',  [ProductCategoryController::class, 'update']);
    Route::delete('/{id}', [ProductCategoryController::class, 'destroy']);
});

Route::prefix('products')->group(function () {
    Route::get('/',        [ProductController::class, 'index']);
    Route::post('/',       [ProductController::class, 'store']);
    Route::get('/{id}',    [ProductController::class, 'show']);
    Route::patch('/{id}',  [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);

    Route::get('/{productId}/variants',  [ProductVariantController::class, 'index']);
    Route::post('/{productId}/variants', [ProductVariantController::class, 'store']);
});

Route::prefix('product-variants')->group(function () {
    Route::get('/{id}',    [ProductVariantController::class, 'show']);
    Route::patch('/{id}',  [ProductVariantController::class, 'update']);
    Route::delete('/{id}', [ProductVariantController::class, 'destroy']);
});
