<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Health Check (public, no tenant required) ─────────────────────────────
Route::prefix('health')->group(function () {
    Route::get('/',         [HealthController::class, 'check']);
    Route::get('/ping',     [HealthController::class, 'ping']);
    Route::get('/ready',    [HealthController::class, 'readiness']);
    Route::get('/live',     [HealthController::class, 'liveness']);
});

// ── Tenant-scoped API routes ───────────────────────────────────────────────
Route::middleware(['tenant'])->prefix('v1')->group(function () {

    // ── Auth (unauthenticated) ─────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
    });

    // ── Authenticated Routes ───────────────────────────────────────────
    Route::middleware(['auth:api'])->group(function () {

        // Auth management
        Route::prefix('auth')->group(function () {
            Route::post('/logout',     [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/me',          [AuthController::class, 'me']);
            Route::post('/refresh',    [AuthController::class, 'refresh']);
            Route::get('/introspect',  [AuthController::class, 'introspect']);
        });

        // ── User Management ───────────────────────────────────────────
        Route::prefix('users')->middleware(['permission:users.view'])->group(function () {
            Route::get('/',              [UserController::class, 'index']);
            Route::get('/{id}',          [UserController::class, 'show']);

            Route::middleware('permission:users.create')->post('/', [UserController::class, 'store']);
            Route::middleware('permission:users.update')->put('/{id}',       [UserController::class, 'update']);
            Route::middleware('permission:users.update')->patch('/{id}',     [UserController::class, 'update']);
            Route::middleware('permission:users.delete')->delete('/{id}',    [UserController::class, 'destroy']);
            Route::middleware('permission:users.update')->patch('/{id}/activate',   [UserController::class, 'activate']);
            Route::middleware('permission:users.update')->patch('/{id}/deactivate', [UserController::class, 'deactivate']);
        });

        // ── Product Management ────────────────────────────────────────
        Route::prefix('products')->middleware(['permission:products.view'])->group(function () {
            Route::get('/',              [ProductController::class, 'index']);
            Route::get('/low-stock',     [ProductController::class, 'lowStock']);
            Route::get('/{id}',          [ProductController::class, 'show']);

            Route::middleware('permission:products.create')->post('/', [ProductController::class, 'store']);
            Route::middleware('permission:products.update')->put('/{id}',    [ProductController::class, 'update']);
            Route::middleware('permission:products.update')->patch('/{id}',  [ProductController::class, 'update']);
            Route::middleware('permission:products.delete')->delete('/{id}', [ProductController::class, 'destroy']);
        });

        // ── Inventory Management ──────────────────────────────────────
        Route::prefix('inventory')->middleware(['permission:inventory.view'])->group(function () {
            Route::get('/',                   [InventoryController::class, 'index']);
            Route::get('/low-stock',          [InventoryController::class, 'lowStock']);
            Route::get('/out-of-stock',       [InventoryController::class, 'outOfStock']);
            Route::get('/product/{productId}',[InventoryController::class, 'show']);

            Route::middleware('permission:inventory.create')->group(function () {
                Route::post('/stock-in',  [InventoryController::class, 'stockIn']);
                Route::post('/stock-out', [InventoryController::class, 'stockOut']);
                Route::post('/adjust',    [InventoryController::class, 'adjust']);
            });
        });

        // ── Order Management ──────────────────────────────────────────
        Route::prefix('orders')->group(function () {
            Route::middleware('permission:orders.view')->group(function () {
                Route::get('/',          [OrderController::class, 'index']);
                Route::get('/my-orders', [OrderController::class, 'myOrders']);
                Route::get('/{id}',      [OrderController::class, 'show']);
            });

            Route::middleware('permission:orders.create')->post('/', [OrderController::class, 'store']);

            Route::middleware('permission:orders.update')->group(function () {
                Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
                Route::patch('/{id}/cancel', [OrderController::class, 'cancel']);
            });
        });

        // ── Webhooks ──────────────────────────────────────────────────
        Route::prefix('webhooks')->middleware(['permission:webhooks.view'])->group(function () {
            Route::get('/',        [WebhookController::class, 'index']);
            Route::get('/{id}',    [WebhookController::class, 'show']);

            Route::middleware('permission:webhooks.create')->post('/', [WebhookController::class, 'store']);
            Route::middleware('permission:webhooks.update')->put('/{id}',    [WebhookController::class, 'update']);
            Route::middleware('permission:webhooks.update')->post('/{id}/test', [WebhookController::class, 'test']);
            Route::middleware('permission:webhooks.delete')->delete('/{id}', [WebhookController::class, 'destroy']);
        });
    });
});
