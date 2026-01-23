<?php

use App\Modules\TenantManagement\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // Tenant routes
    Route::apiResource('tenants', TenantController::class);
    Route::post('tenants/{id}/activate', [TenantController::class, 'activate']);
    Route::post('tenants/{id}/suspend', [TenantController::class, 'suspend']);
    Route::post('tenants/{id}/subscription', [TenantController::class, 'updateSubscription']);
    Route::post('tenants/{id}/subscription/renew', [TenantController::class, 'renewSubscription']);
});
