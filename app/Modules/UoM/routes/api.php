<?php

use Illuminate\Support\Facades\Route;
use Modules\UoM\Infrastructure\Http\Controllers\ProductUomSettingController;
use Modules\UoM\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\UoM\Infrastructure\Http\Controllers\UomCategoryController;
use Modules\UoM\Infrastructure\Http\Controllers\UomConversionController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::prefix('uom')->group(function () {
        Route::apiResource('categories', UomCategoryController::class);
        Route::apiResource('units', UnitOfMeasureController::class);
        Route::apiResource('conversions', UomConversionController::class);
        Route::apiResource('product-settings', ProductUomSettingController::class);
    });
});
