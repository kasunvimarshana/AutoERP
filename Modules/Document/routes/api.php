<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Document\Http\Controllers\DocumentController;
use Modules\Document\Http\Controllers\DocumentShareController;
use Modules\Document\Http\Controllers\DocumentTagController;
use Modules\Document\Http\Controllers\DocumentVersionController;
use Modules\Document\Http\Controllers\FolderController;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/documents')->group(function () {

    // Documents
    Route::apiResource('documents', DocumentController::class);
    Route::post('documents/{document}/move', [DocumentController::class, 'move']);
    Route::post('documents/{document}/copy', [DocumentController::class, 'copy']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('documents/{document}/stream', [DocumentController::class, 'stream']);
    Route::get('documents/{document}/url', [DocumentController::class, 'getUrl']);
    Route::post('documents/{id}/restore', [DocumentController::class, 'restore']);

    // Folders
    Route::apiResource('folders', FolderController::class);
    Route::get('folders/{folder}/breadcrumbs', [FolderController::class, 'breadcrumbs']);
    Route::get('folders/{folder}/children', [FolderController::class, 'children']);
    Route::post('folders/{folder}/move', [FolderController::class, 'move']);

    // Document Versions
    Route::get('documents/{document}/versions', [DocumentVersionController::class, 'index']);
    Route::get('documents/{document}/versions/{versionNumber}', [DocumentVersionController::class, 'show']);
    Route::post('documents/{document}/versions/{versionNumber}/restore', [DocumentVersionController::class, 'restore']);
    Route::get('documents/{document}/versions/{version1}/compare/{version2}', [DocumentVersionController::class, 'compare']);
    Route::get('documents/{document}/versions/{versionId}/download', [DocumentVersionController::class, 'download']);
    Route::post('documents/{document}/versions/cleanup', [DocumentVersionController::class, 'cleanup']);

    // Document Sharing
    Route::get('documents/{document}/shares', [DocumentShareController::class, 'index']);
    Route::post('documents/{document}/shares', [DocumentShareController::class, 'store']);
    Route::post('documents/{document}/shares/bulk', [DocumentShareController::class, 'bulkShare']);
    Route::patch('shares/{share}', [DocumentShareController::class, 'update']);
    Route::delete('shares/{share}', [DocumentShareController::class, 'destroy']);
    Route::get('shares/shared-with-me', [DocumentShareController::class, 'sharedWithMe']);
    Route::get('documents/{document}/permissions', [DocumentShareController::class, 'permissions']);
    Route::post('documents/{document}/check-permission', [DocumentShareController::class, 'checkPermission']);

    // Document Tags
    Route::apiResource('tags', DocumentTagController::class);
    Route::get('tags/{tag}/documents', [DocumentTagController::class, 'documents']);
});
