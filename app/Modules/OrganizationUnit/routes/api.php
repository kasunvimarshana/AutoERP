<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitDocumentController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitSettingController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitSettingGroupController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitTypeController;

Route::prefix('api/organization-unit')
    ->middleware('api')
    ->name('organization-unit.')
    ->group(function (): void {
        Route::apiResource('organization-unit-types', OrganizationUnitTypeController::class);
        Route::apiResource('organization-units', OrganizationUnitController::class);
        Route::apiResource('organization-unit-setting-groups', OrganizationUnitSettingGroupController::class);
        Route::apiResource('organization-unit-settings', OrganizationUnitSettingController::class);
        Route::apiResource('organization-unit-documents', OrganizationUnitDocumentController::class);
    });