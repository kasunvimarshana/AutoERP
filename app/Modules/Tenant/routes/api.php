<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantAttachmentController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('tenants', TenantController::class);
    Route::patch('tenants/{tenant}/config', [TenantController::class, 'updateConfig']);

    // Attachment management
    Route::get('tenants/{tenant}/attachments',          [TenantAttachmentController::class, 'index']);
    Route::post('tenants/{tenant}/attachments',          [TenantAttachmentController::class, 'store']);
    Route::post('tenants/{tenant}/attachments/bulk',     [TenantAttachmentController::class, 'storeMany']);
    Route::delete('tenants/{tenant}/attachments/{attachment}', [TenantAttachmentController::class, 'destroy']);
});

// Internal endpoint for other services
Route::get('config/domain/{domain}', [TenantController::class, 'configByDomain']);

// File serving (authenticated)
Route::get('storage/tenant-attachments/{uuid}', [TenantAttachmentController::class, 'serve'])->middleware('auth:api');
