<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ProductBrandController;
use Modules\Product\Infrastructure\Http\Controllers\ProductCategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductIdentifierController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariantController;
use Modules\Product\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\Product\Infrastructure\Http\Controllers\UomConversionController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-identifiers', ProductIdentifierController::class);
    Route::apiResource('product-variants', ProductVariantController::class);
    Route::apiResource('product-brands', ProductBrandController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('uom-conversions', UomConversionController::class);
    Route::apiResource('units-of-measure', UnitOfMeasureController::class);
});
