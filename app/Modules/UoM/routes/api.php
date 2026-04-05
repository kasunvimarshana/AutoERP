<?php
use Illuminate\Support\Facades\Route;
use Modules\UoM\Infrastructure\Http\Controllers\UomCategoryController;
use Modules\UoM\Infrastructure\Http\Controllers\UnitOfMeasureController;
Route::prefix('api')->group(function () {
    Route::apiResource('uom/categories', UomCategoryController::class);
    Route::apiResource('uom/units', UnitOfMeasureController::class);
});
