<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\KeycloakAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – Product Service
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // Health-check (unauthenticated)
    Route::get('/health', fn () => response()->json([
        'status'    => 'ok',
        'service'   => 'product-service',
        'timestamp' => now()->toIso8601String(),
    ]));

    // All product routes require a valid Keycloak JWT
    Route::middleware([KeycloakAuth::class])->group(function (): void {

        // Read operations – any authenticated user
        Route::get('/products',       [ProductController::class, 'index']);
        Route::get('/products/{id}',  [ProductController::class, 'show'])->whereNumber('id');

        // Write operations – restricted to admin or manager roles
        Route::middleware([RoleMiddleware::class . ':admin,manager'])->group(function (): void {
            Route::post('/products',          [ProductController::class, 'store']);
            Route::put('/products/{id}',      [ProductController::class, 'update'])->whereNumber('id');
            Route::patch('/products/{id}',    [ProductController::class, 'update'])->whereNumber('id');
            Route::delete('/products/{id}',   [ProductController::class, 'destroy'])->whereNumber('id');
        });
    });
});
