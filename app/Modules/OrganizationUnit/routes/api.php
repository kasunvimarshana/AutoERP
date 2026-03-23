<?php

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitAttachmentController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('org-units', OrganizationUnitController::class);
    Route::get('org-units/tree', [OrganizationUnitController::class, 'tree']);
    Route::patch('org-units/{unit}/move', [OrganizationUnitController::class, 'move']);

    Route::get('org-units/{unit}/attachments', [OrganizationUnitAttachmentController::class, 'index']);
    Route::post('org-units/{unit}/attachments', [OrganizationUnitAttachmentController::class, 'store']);
    Route::delete('org-units/{unit}/attachments/{attachment}', [OrganizationUnitAttachmentController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/org-unit-attachments/{uuid}', [OrganizationUnitAttachmentController::class, 'serve'])->middleware('auth:api');

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
