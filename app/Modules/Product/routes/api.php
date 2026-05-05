<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ComboItemController;
use Modules\Product\Infrastructure\Http\Controllers\ProductAttributeController;
use Modules\Product\Infrastructure\Http\Controllers\ProductAttributeGroupController;
use Modules\Product\Infrastructure\Http\Controllers\ProductAttributeValueController;
use Modules\Product\Infrastructure\Http\Controllers\ProductBrandController;
use Modules\Product\Infrastructure\Http\Controllers\ProductCategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductIdentifierController;
use Modules\Product\Infrastructure\Http\Controllers\ProductSearchController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariantController;
use Modules\Product\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\Product\Infrastructure\Http\Controllers\UomConversionController;
use Modules\Product\Infrastructure\Http\Controllers\VariantAttributeController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::get('products/search', [ProductSearchController::class, 'index']);
    Route::post('products/search/rebuild', [ProductSearchController::class, 'rebuild']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-identifiers', ProductIdentifierController::class);
    Route::apiResource('product-variants', ProductVariantController::class);
    Route::apiResource('product-brands', ProductBrandController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::post('uom-conversions/resolve', [UomConversionController::class, 'resolve']);
    Route::apiResource('uom-conversions', UomConversionController::class);
    Route::apiResource('units-of-measure', UnitOfMeasureController::class);
    Route::apiResource('product-attribute-groups', ProductAttributeGroupController::class);
    Route::apiResource('product-attributes', ProductAttributeController::class);
    Route::apiResource('product-attribute-values', ProductAttributeValueController::class);
    Route::apiResource('variant-attributes', VariantAttributeController::class);
    Route::apiResource('combo-items', ComboItemController::class);
});
