<?php

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitAttachmentController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    // Static routes must be declared BEFORE the resource wildcard route to
    // prevent the {org_unit} segment from swallowing them.
    Route::get('org-units/tree', [OrganizationUnitController::class, 'tree']);

    Route::apiResource('org-units', OrganizationUnitController::class);

    // Hierarchical read routes — all logic delegated to FindOrganizationUnitServiceInterface
    Route::get('org-units/{unit}/descendants', [OrganizationUnitController::class, 'descendants']);
    Route::get('org-units/{unit}/ancestors',   [OrganizationUnitController::class, 'ancestors']);

    // Move to a different parent
    Route::patch('org-units/{unit}/move', [OrganizationUnitController::class, 'move']);

    // Attachment routes
    Route::get('org-units/{unit}/attachments',                              [OrganizationUnitAttachmentController::class, 'index']);
    Route::post('org-units/{unit}/attachments',                             [OrganizationUnitAttachmentController::class, 'store']);
    Route::post('org-units/{unit}/attachments/bulk',                        [OrganizationUnitAttachmentController::class, 'storeMany']);
    Route::patch('org-units/{unit}/attachments/{attachment}',               [OrganizationUnitAttachmentController::class, 'update']);
    Route::post('org-units/{unit}/attachments/{attachment}/replace',        [OrganizationUnitAttachmentController::class, 'replace']);
    Route::delete('org-units/{unit}/attachments/{attachment}',              [OrganizationUnitAttachmentController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/org-unit-attachments/{uuid}', [OrganizationUnitAttachmentController::class, 'serve'])->middleware('auth:api');
