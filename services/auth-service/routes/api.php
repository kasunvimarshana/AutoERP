<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Auth Service
|--------------------------------------------------------------------------
*/

// ── Health check (no auth, no tenant required) ────────────────────────────
Route::get('/health', HealthController::class)->name('health');

// ── Tenant-aware public routes ────────────────────────────────────────────
Route::middleware(['resolve.tenant'])->group(function (): void {

    // Authentication
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login',    [AuthController::class, 'login'])->name('login');
    });

    // Authenticated routes
    Route::middleware('auth:api')->group(function (): void {

        Route::prefix('auth')->name('auth.')->group(function (): void {
            Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');
            Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
            Route::get('/me',       [AuthController::class, 'me'])->name('me');
        });

        // Tenant management (admin only)
        Route::prefix('tenants')->name('tenants.')->middleware('can:manage-tenants')->group(function (): void {
            Route::get('/',          [TenantController::class, 'index'])->name('index');
            Route::post('/',         [TenantController::class, 'store'])->name('store');
            Route::get('/{id}',      [TenantController::class, 'show'])->name('show');
            Route::put('/{id}',      [TenantController::class, 'update'])->name('update');
            Route::delete('/{id}',   [TenantController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/config', [TenantController::class, 'updateConfig'])->name('updateConfig');
        });

        // Webhook management
        Route::prefix('webhooks')->name('webhooks.')->group(function (): void {
            Route::get('/',                       [WebhookController::class, 'index'])->name('index');
            Route::post('/',                      [WebhookController::class, 'store'])->name('store');
            Route::delete('/{id}',                [WebhookController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/test',             [WebhookController::class, 'test'])->name('test');
            Route::get('/{id}/deliveries',        [WebhookController::class, 'deliveries'])->name('deliveries');
        });
    });
});
