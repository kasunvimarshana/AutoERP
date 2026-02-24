<?php

use Illuminate\Support\Facades\Route;
use Modules\DocumentManagement\Presentation\Controllers\DocumentCategoryController;
use Modules\DocumentManagement\Presentation\Controllers\DocumentController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('documents/categories', DocumentCategoryController::class)->except(['update']);
    Route::put('documents/categories/{id}', [DocumentCategoryController::class, 'update']);
    Route::apiResource('documents', DocumentController::class)->except(['update']);
    Route::put('documents/{id}', [DocumentController::class, 'update']);
    Route::post('documents/{id}/publish', [DocumentController::class, 'publish']);
    Route::post('documents/{id}/archive', [DocumentController::class, 'archive']);
});
