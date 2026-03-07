<?php

declare(strict_types=1);

use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Middleware\KeycloakAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – Inventory Service
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // Health-check (unauthenticated)
    Route::get('/health', fn () => response()->json([
        'status'    => 'ok',
        'service'   => 'inventory-service',
        'timestamp' => now()->toIso8601String(),
    ]));

    // All inventory routes require a valid Keycloak JWT
    Route::middleware([KeycloakAuth::class])->group(function (): void {

        // ── Inventory ────────────────────────────────────────────────────────

        // Read operations – any authenticated user
        Route::get('/inventory',                               [InventoryController::class, 'index']);
        Route::get('/inventory/{id}',                          [InventoryController::class, 'show'])->whereNumber('id');
        Route::get('/inventory/product/{productId}',           [InventoryController::class, 'getInventoryByProductId'])->whereNumber('productId');

        // Write operations – restricted to admin or manager roles
        Route::middleware([RoleMiddleware::class . ':admin,manager'])->group(function (): void {
            Route::post('/inventory',                          [InventoryController::class, 'store']);
            Route::put('/inventory/{id}',                     [InventoryController::class, 'update'])->whereNumber('id');
            Route::patch('/inventory/{id}',                   [InventoryController::class, 'update'])->whereNumber('id');
            Route::delete('/inventory/{id}',                  [InventoryController::class, 'destroy'])->whereNumber('id');
            Route::post('/inventory/{id}/adjust',             [InventoryController::class, 'adjustInventory'])->whereNumber('id');
        });

        // ── Transactions (read-only) ──────────────────────────────────────────
        Route::get('/inventory-transactions',                  [InventoryTransactionController::class, 'index']);
        Route::get('/inventory-transactions/{id}',             [InventoryTransactionController::class, 'show'])->whereNumber('id');
    });
});
