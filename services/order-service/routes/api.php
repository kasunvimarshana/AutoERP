<?php

declare(strict_types=1);

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Middleware\KeycloakAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – Order Service
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // Health-check (unauthenticated)
    Route::get('/health', fn () => response()->json([
        'status'    => 'ok',
        'service'   => 'order-service',
        'timestamp' => now()->toIso8601String(),
    ]));

    // All order routes require a valid Keycloak JWT
    Route::middleware([KeycloakAuth::class])->group(function (): void {

        // ── Customer self-service ────────────────────────────────────────────
        // Must be declared before the /{id} wildcard routes
        Route::get('/orders/my-orders', [OrderController::class, 'myOrders']);

        // ── Orders (read) – any authenticated user ───────────────────────────
        Route::get('/orders',       [OrderController::class, 'index']);
        Route::get('/orders/{id}',  [OrderController::class, 'show'])->whereNumber('id');

        // ── Place order – any authenticated user ─────────────────────────────
        Route::post('/orders', [OrderController::class, 'store']);

        // ── Order lifecycle actions ───────────────────────────────────────────
        // Cancel – customer or admin
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');

        // Confirm / Ship / Deliver – restricted to admin or manager roles
        Route::middleware([RoleMiddleware::class . ':admin,manager'])->group(function (): void {
            Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm'])->whereNumber('id');
            Route::post('/orders/{id}/ship',    [OrderController::class, 'ship'])->whereNumber('id');
            Route::post('/orders/{id}/deliver', [OrderController::class, 'deliver'])->whereNumber('id');

            Route::put('/orders/{id}',    [OrderController::class, 'update'])->whereNumber('id');
            Route::patch('/orders/{id}',  [OrderController::class, 'update'])->whereNumber('id');
            Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->whereNumber('id');
        });

        // ── Order items (read-only) ───────────────────────────────────────────
        Route::get('/orders/{orderId}/items',           [OrderItemController::class, 'index'])->whereNumber('orderId');
        Route::get('/orders/{orderId}/items/{itemId}',  [OrderItemController::class, 'show'])->whereNumber(['orderId', 'itemId']);
    });
});
