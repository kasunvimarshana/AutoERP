<?php

use Illuminate\Support\Facades\Route;
use Modules\Brand\Infrastructure\Http\Controllers\BrandController;
use Modules\Brand\Infrastructure\Http\Controllers\BrandLogoController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('brands', BrandController::class);

    Route::post('brands/{brand}/logo', [BrandLogoController::class, 'store']);
    Route::delete('brands/{brand}/logo/{logo}', [BrandLogoController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/brand-logos/{uuid}', [BrandLogoController::class, 'serve'])->middleware('auth:api');
