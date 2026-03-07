<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Inventory\ProductController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\Tenant\TenantController;
use App\Http\Controllers\Api\V1\Health\HealthController;
use App\Http\Controllers\Api\Webhook\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/.
| Tenant resolution is applied to the protected group.
|
*/

// Health checks — no auth, no tenant context required.
Route::prefix('v1/health')->group(function (): void {
    Route::get('/', [HealthController::class, 'liveness'])->name('health.liveness');
    Route::get('/ready', [HealthController::class, 'readiness'])->name('health.readiness');
});

// Authentication endpoints — no auth required (except logout/me).
Route::prefix('v1/auth')->middleware(['tenant'])->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware(['auth:api'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/roles', [AuthController::class, 'assignRole'])
            ->middleware('role:super-admin,admin')
            ->name('auth.assign-role');
    });
});

// Protected API routes — require authentication and tenant context.
Route::prefix('v1')->middleware(['auth:api', 'tenant'])->group(function (): void {

    // -----------------------------------------------------------------------
    // Inventory / Products
    // -----------------------------------------------------------------------
    Route::prefix('products')->group(function (): void {
        Route::get('/low-stock', [ProductController::class, 'lowStock'])->name('products.low-stock');
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::get('/{id}', [ProductController::class, 'show'])->name('products.show');
        Route::put('/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/{id}/stock', [ProductController::class, 'adjustStock'])->name('products.stock');
    });

    // -----------------------------------------------------------------------
    // Orders
    // -----------------------------------------------------------------------
    Route::prefix('orders')->group(function (): void {
        Route::get('/summary', [OrderController::class, 'summary'])->name('orders.summary');
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });

    // -----------------------------------------------------------------------
    // Tenants (super-admin only)
    // -----------------------------------------------------------------------
    Route::prefix('tenants')
        ->middleware('role:super-admin')
        ->group(function (): void {
            Route::get('/', [TenantController::class, 'index'])->name('tenants.index');
            Route::post('/', [TenantController::class, 'store'])->name('tenants.store');
            Route::get('/{id}', [TenantController::class, 'show'])->name('tenants.show');
            Route::put('/{id}', [TenantController::class, 'update'])->name('tenants.update');
            Route::delete('/{id}', [TenantController::class, 'destroy'])->name('tenants.destroy');
            Route::patch('/{id}/config', [TenantController::class, 'updateConfig'])->name('tenants.config');
        });

    // -----------------------------------------------------------------------
    // Webhooks
    // -----------------------------------------------------------------------
    Route::prefix('webhooks')->group(function (): void {
        Route::get('/', [WebhookController::class, 'index'])->name('webhooks.index');
        Route::post('/', [WebhookController::class, 'store'])->name('webhooks.store');
        Route::delete('/{id}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    });
});

// Incoming webhook receiver — public, signature-verified.
Route::post('/webhooks/receive', [WebhookController::class, 'receive'])->name('webhooks.receive');
