<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Presentation\Http\Controllers\TenantDocumentController;
use Modules\Tenant\Presentation\Http\Controllers\TenantDomainController;
use Modules\Tenant\Presentation\Http\Controllers\TenantPlanController;
use Modules\Tenant\Presentation\Http\Controllers\TenantSettingController;
use Modules\Tenant\Presentation\Http\Controllers\TenantSettingGroupController;
use Modules\Tenant\Presentation\Http\Controllers\TenantController;

Route::prefix('api/tenant')
    ->middleware('api')
    ->name('tenant.')
    ->group(function (): void {
        Route::apiResource('tenants', TenantController::class)->except(['destroy']);
        Route::apiResource('plans', TenantPlanController::class);
        Route::apiResource('setting-groups', TenantSettingGroupController::class);
        Route::apiResource('settings', TenantSettingController::class);
        Route::apiResource('documents', TenantDocumentController::class);
        Route::apiResource('domains', TenantDomainController::class);
        Route::patch('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::patch('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::patch('tenants/{tenant}/deactivate', [TenantController::class, 'deactivate'])
            ->name('tenants.deactivate');
    });
