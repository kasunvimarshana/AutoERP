<?php
use Illuminate\Support\Facades\Route;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;

Route::prefix('tenants')->group(function () {
    Route::get('/',        [TenantController::class, 'index']);
    Route::post('/',       [TenantController::class, 'store']);
    Route::get('/{id}',    [TenantController::class, 'show']);
    Route::patch('/{id}',  [TenantController::class, 'update']);
    Route::delete('/{id}', [TenantController::class, 'destroy']);
});
