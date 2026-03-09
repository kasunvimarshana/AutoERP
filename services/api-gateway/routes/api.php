<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - API Gateway
|--------------------------------------------------------------------------
*/

// Health check endpoints (no auth, no tenant middleware)
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'health'])->name('health.index');
    Route::get('/ping', [HealthController::class, 'ping'])->name('health.ping');
});

// Public auth routes (tenant middleware required)
Route::prefix('v1')->middleware(['tenant'])->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    });

    // Protected routes
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Tenant management (super-admin only)
        Route::prefix('tenants')->middleware(['role:super-admin'])->group(function () {
            Route::post('/', [TenantController::class, 'store'])->name('tenants.store');
            Route::get('/current', [TenantController::class, 'show'])->name('tenants.show');
            Route::put('/{tenantId}/config', [TenantController::class, 'updateConfig'])->name('tenants.config.update');
        });
    });
});
