<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Presentation\Http\Controllers\TenantController;
use Modules\Tenant\Presentation\Http\Controllers\TenantDocumentController;
use Modules\Tenant\Presentation\Http\Controllers\TenantDomainController;
use Modules\Tenant\Presentation\Http\Controllers\TenantPlanController;
use Modules\Tenant\Presentation\Http\Controllers\TenantSettingController;
use Modules\Tenant\Presentation\Http\Controllers\TenantSettingGroupController;

Route::prefix('api/tenant')
    ->middleware('api')
    ->name('tenant.')
    ->group(function (): void {
        Route::apiResource('plans', TenantPlanController::class)->parameters(['plans' => 'plan']);
        Route::apiResource('tenants', TenantController::class);

        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::apiResource('domains', TenantDomainController::class);
                Route::apiResource('setting-groups', TenantSettingGroupController::class)
                    ->parameters(['setting-groups' => 'setting_group']);
                Route::apiResource('settings', TenantSettingController::class);
                Route::apiResource('documents', TenantDocumentController::class);
            });
    });
