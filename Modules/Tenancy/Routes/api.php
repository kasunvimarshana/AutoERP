<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\Api\TenantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Tenant routes
    Route::apiResource('tenants', TenantController::class);

    // Custom tenant routes
    Route::prefix('tenants')->group(function () {
        Route::get('search', [TenantController::class, 'search'])->name('tenants.search');
        Route::get('active', [TenantController::class, 'active'])->name('tenants.active');
        Route::post('{id}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('{id}/deactivate', [TenantController::class, 'deactivate'])->name('tenants.deactivate');
        Route::post('{id}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    });
});
