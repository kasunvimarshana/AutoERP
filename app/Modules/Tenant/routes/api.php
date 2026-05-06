<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantAttachmentController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantDomainController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantPlanController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantSettingController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function () {
    Route::apiResource('tenants', TenantController::class);
    Route::patch('tenants/{tenant}/config', [TenantController::class, 'updateConfig']);

    // Attachment management
    Route::get('tenants/{tenant}/attachments', [TenantAttachmentController::class, 'index']);
    Route::post('tenants/{tenant}/attachments', [TenantAttachmentController::class, 'store']);
    Route::post('tenants/{tenant}/attachments/bulk', [TenantAttachmentController::class, 'storeMany']);
    Route::delete('tenants/{tenant}/attachments/{attachment}', [TenantAttachmentController::class, 'destroy']);

    // Plan and settings read endpoints
    Route::get('tenant-plans', [TenantPlanController::class, 'index']);
    Route::get('tenant-plans/{plan}', [TenantPlanController::class, 'show']);
    Route::post('tenant-plans', [TenantPlanController::class, 'store']);
    Route::put('tenant-plans/{plan}', [TenantPlanController::class, 'update']);
    Route::patch('tenant-plans/{plan}', [TenantPlanController::class, 'update']);
    Route::delete('tenant-plans/{plan}', [TenantPlanController::class, 'destroy']);

    // Tenant domain management
    Route::get('tenants/{tenant}/domains', [TenantDomainController::class, 'index']);
    Route::get('tenants/{tenant}/domains/{domain}', [TenantDomainController::class, 'show']);
    Route::post('tenants/{tenant}/domains', [TenantDomainController::class, 'store']);
    Route::put('tenants/{tenant}/domains/{domain}', [TenantDomainController::class, 'update']);
    Route::patch('tenants/{tenant}/domains/{domain}', [TenantDomainController::class, 'update']);
    Route::delete('tenants/{tenant}/domains/{domain}', [TenantDomainController::class, 'destroy']);

    Route::get('tenants/{tenant}/settings', [TenantSettingController::class, 'index']);
    Route::get('tenants/{tenant}/settings/{key}', [TenantSettingController::class, 'show']);
    Route::post('tenants/{tenant}/settings', [TenantSettingController::class, 'store']);
    Route::put('tenants/{tenant}/settings/{key}', [TenantSettingController::class, 'update']);
    Route::patch('tenants/{tenant}/settings/{key}', [TenantSettingController::class, 'update']);
    Route::delete('tenants/{tenant}/settings/{key}', [TenantSettingController::class, 'destroy']);
});

// Internal endpoint for other services
Route::get('config/domain/{domain}', [TenantController::class, 'configByDomain']);

// File serving (authenticated)
Route::get('storage/tenant-attachments/{uuid}', [TenantAttachmentController::class, 'serve'])->middleware('auth.configured');
