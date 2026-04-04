<?php
use Illuminate\Support\Facades\Route;
use Modules\UoM\Infrastructure\Http\Controllers\ProductUomSettingController;
use Modules\UoM\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\UoM\Infrastructure\Http\Controllers\UomCategoryController;
use Modules\UoM\Infrastructure\Http\Controllers\UomConversionController;

Route::prefix('uom')->group(function () {
    Route::get('/categories',          [UomCategoryController::class, 'index']);
    Route::post('/categories',         [UomCategoryController::class, 'store']);
    Route::get('/categories/{id}',     [UomCategoryController::class, 'show']);
    Route::patch('/categories/{id}',   [UomCategoryController::class, 'update']);
    Route::delete('/categories/{id}',  [UomCategoryController::class, 'destroy']);

    Route::get('/units',               [UnitOfMeasureController::class, 'index']);
    Route::post('/units',              [UnitOfMeasureController::class, 'store']);
    Route::get('/units/{id}',          [UnitOfMeasureController::class, 'show']);
    Route::patch('/units/{id}',        [UnitOfMeasureController::class, 'update']);
    Route::delete('/units/{id}',       [UnitOfMeasureController::class, 'destroy']);

    Route::get('/conversions',         [UomConversionController::class, 'index']);
    Route::post('/conversions',        [UomConversionController::class, 'store']);
    Route::get('/conversions/{id}',    [UomConversionController::class, 'show']);
    Route::patch('/conversions/{id}',  [UomConversionController::class, 'update']);
    Route::delete('/conversions/{id}', [UomConversionController::class, 'destroy']);

    Route::get('/product-settings',        [ProductUomSettingController::class, 'index']);
    Route::post('/product-settings',       [ProductUomSettingController::class, 'store']);
    Route::get('/product-settings/{id}',   [ProductUomSettingController::class, 'show']);
    Route::patch('/product-settings/{id}', [ProductUomSettingController::class, 'update']);
});
