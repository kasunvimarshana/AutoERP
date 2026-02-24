<?php

use Illuminate\Support\Facades\Route;
use Modules\AssetManagement\Presentation\Controllers\AssetCategoryController;
use Modules\AssetManagement\Presentation\Controllers\AssetController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('assets/categories', AssetCategoryController::class);
    Route::apiResource('assets', AssetController::class)->except(['update']);
    Route::put('assets/{id}', [AssetController::class, 'update']);
    Route::post('assets/{id}/dispose', [AssetController::class, 'dispose']);
    Route::post('assets/{id}/depreciate', [AssetController::class, 'depreciate']);
});
