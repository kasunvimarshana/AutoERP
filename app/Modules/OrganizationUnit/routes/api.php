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
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::apiResource('types', OrganizationUnitTypeController::class);
                Route::apiResource('units', OrganizationUnitController::class);

                Route::prefix('units/{unit}')
                    ->name('units.')
                    ->group(function (): void {
                        Route::apiResource('setting-groups', OrganizationUnitSettingGroupController::class)
                            ->parameters(['setting-groups' => 'setting_group']);
                        Route::apiResource('settings', OrganizationUnitSettingController::class);
                        Route::apiResource('documents', OrganizationUnitDocumentController::class);
                    });
            });
    });
