<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Tenancy\Http\Controllers\TenantController;

Route::prefix('api/tenancy')->middleware(['api'])->group(function () {
    // Tenant routes
    Route::prefix('tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('/{id}', [TenantController::class, 'show']);
        Route::put('/{id}', [TenantController::class, 'update']);
        Route::delete('/{id}', [TenantController::class, 'destroy']);
    });
});
