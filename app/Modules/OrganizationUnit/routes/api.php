<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitAttachmentController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('organization-units', OrganizationUnitController::class);

    Route::get('organization-units/{organization_unit}/attachments', [OrganizationUnitAttachmentController::class, 'index']);
    Route::post('organization-units/{organization_unit}/attachments', [OrganizationUnitAttachmentController::class, 'store']);
    Route::delete('organization-units/{organization_unit}/attachments/{attachment}', [OrganizationUnitAttachmentController::class, 'destroy']);
});

Route::get('storage/org-unit-attachments/{uuid}', [OrganizationUnitAttachmentController::class, 'serve'])->middleware('auth:api');
