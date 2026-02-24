<?php
use Illuminate\Support\Facades\Route;
use Modules\Tenant\Presentation\Controllers\TenantController;
Route::prefix('api/v1')->middleware('api')->group(function () {
    Route::apiResource('tenants', TenantController::class)->except(['destroy']);
    Route::post('tenants/{id}/suspend', [TenantController::class, 'suspend']);
});
