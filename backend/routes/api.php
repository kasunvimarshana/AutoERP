<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TenantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 routes
Route::prefix('v1')->group(function () {
    
    // Tenant management routes
    Route::prefix('tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('/{id}', [TenantController::class, 'show']);
        Route::put('/{id}', [TenantController::class, 'update']);
        Route::delete('/{id}', [TenantController::class, 'destroy']);
        
        // Tenant status management
        Route::post('/{id}/activate', [TenantController::class, 'activate']);
        Route::post('/{id}/suspend', [TenantController::class, 'suspend']);
        Route::post('/{id}/cancel', [TenantController::class, 'cancel']);
        Route::post('/{id}/extend-trial', [TenantController::class, 'extendTrial']);
    });
    
});
