<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Service API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1 and return JSON.
| Tenant-scoped routes require a valid X-Tenant-ID header.
|
*/

Route::prefix('v1')->group(function () {

    // ── Health check ────────────────────────────────────────────────────
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'auth-service']));

    // ── Public authentication routes ────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
        Route::post('/refresh',  [AuthController::class, 'refresh']);
    });

    // ── Protected routes (require valid Passport Bearer token) ──────────
    Route::middleware('auth:api')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me',      [AuthController::class, 'me']);
        });

        // ── Tenant management (super-admin only) ────────────────────────
        Route::middleware('role:super-admin')->group(function () {
            Route::apiResource('tenants', TenantController::class);
        });
    });
});
