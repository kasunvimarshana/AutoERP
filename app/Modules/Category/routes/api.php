<?php

use Illuminate\Support\Facades\Route;
use Modules\Category\Infrastructure\Http\Controllers\CategoryController;
use Modules\Category\Infrastructure\Http\Controllers\CategoryImageController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::get('categories/roots', [CategoryController::class, 'roots']);
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/{category}/tree', [CategoryController::class, 'tree']);

    Route::post('categories/{category}/image', [CategoryImageController::class, 'store']);
    Route::delete('categories/{category}/image/{image}', [CategoryImageController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/category-images/{uuid}', [CategoryImageController::class, 'serve'])->middleware('auth:api');
