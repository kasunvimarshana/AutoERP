<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\CategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariantController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::get('categories/tree', [CategoryController::class, 'tree']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('products.variants', ProductVariantController::class)->shallow();
});
