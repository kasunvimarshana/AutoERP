<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitAttachmentController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitTypeController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitUserController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('organization-units', OrganizationUnitController::class);

    Route::apiResource('organization-unit-types', OrganizationUnitTypeController::class);

    Route::get('organization-units/{organization_unit}/users', [OrganizationUnitUserController::class, 'index']);
    Route::get('organization-units/{organization_unit}/users/{organization_unit_user}', [OrganizationUnitUserController::class, 'show']);
    Route::post('organization-units/{organization_unit}/users', [OrganizationUnitUserController::class, 'store']);
    Route::put('organization-units/{organization_unit}/users/{organization_unit_user}', [OrganizationUnitUserController::class, 'update']);
    Route::patch('organization-units/{organization_unit}/users/{organization_unit_user}', [OrganizationUnitUserController::class, 'update']);
    Route::delete('organization-units/{organization_unit}/users/{organization_unit_user}', [OrganizationUnitUserController::class, 'destroy']);

    Route::get('organization-units/{organization_unit}/attachments', [OrganizationUnitAttachmentController::class, 'index']);
    Route::post('organization-units/{organization_unit}/attachments', [OrganizationUnitAttachmentController::class, 'store']);
    Route::delete('organization-units/{organization_unit}/attachments/{attachment}', [OrganizationUnitAttachmentController::class, 'destroy']);
});

Route::get('storage/org-unit-attachments/{uuid}', [OrganizationUnitAttachmentController::class, 'serve'])->middleware('auth:api');
