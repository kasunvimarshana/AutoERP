<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\AttributeController;
use Modules\Product\Infrastructure\Http\Controllers\AttributeGroupController;
use Modules\Product\Infrastructure\Http\Controllers\AttributeValueController;
use Modules\Product\Infrastructure\Http\Controllers\BatchController;
use Modules\Product\Infrastructure\Http\Controllers\ComboItemController;
use Modules\Product\Infrastructure\Http\Controllers\ProductAttachmentController;
use Modules\Product\Infrastructure\Http\Controllers\ProductBrandController;
use Modules\Product\Infrastructure\Http\Controllers\ProductCategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductIdentifierController;
use Modules\Product\Infrastructure\Http\Controllers\ProductSupplierPriceController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariantController;
use Modules\Product\Infrastructure\Http\Controllers\SerialController;
use Modules\Product\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\Product\Infrastructure\Http\Controllers\UomConversionController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::patch('products/{product}/publish', [ProductController::class, 'publish']);
    Route::patch('products/{product}/archive', [ProductController::class, 'archive']);
    Route::patch('products/{product}/discontinue', [ProductController::class, 'discontinue']);
    Route::patch('products/{product}/draft', [ProductController::class, 'draft']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-identifiers', ProductIdentifierController::class);
    Route::apiResource('product-variants', ProductVariantController::class);
    Route::apiResource('product-brands', ProductBrandController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('uom-conversions', UomConversionController::class);
    Route::apiResource('units-of-measure', UnitOfMeasureController::class);
    Route::apiResource('attribute-groups', AttributeGroupController::class);
    Route::apiResource('attributes', AttributeController::class);
    Route::apiResource('attribute-values', AttributeValueController::class);
    Route::apiResource('batches', BatchController::class);
    Route::apiResource('serials', SerialController::class);
    Route::apiResource('product-attachments', ProductAttachmentController::class);
    Route::apiResource('product-supplier-prices', ProductSupplierPriceController::class);
    Route::apiResource('combo-items', ComboItemController::class);
});
