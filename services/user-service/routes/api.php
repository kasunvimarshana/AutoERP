<?php

declare(strict_types=1);

use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\KeycloakAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – User Service
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // Health-check (unauthenticated)
    Route::get('/health', fn () => response()->json([
        'status'    => 'ok',
        'service'   => 'user-service',
        'timestamp' => now()->toIso8601String(),
    ]));

    // All user routes require a valid Keycloak JWT
    Route::middleware([KeycloakAuth::class])->group(function (): void {

        // Current user profile (any authenticated user)
        Route::get('/profile',  [UserController::class, 'getUserProfile']);
        Route::put('/profile',  [UserController::class, 'updateMyProfile']);

        // Read operations – any authenticated user
        Route::get('/users',       [UserController::class, 'index']);
        Route::get('/users/{id}',  [UserController::class, 'show'])->whereNumber('id');

        // Write operations – restricted to admin role
        Route::middleware([RoleMiddleware::class . ':admin'])->group(function (): void {
            Route::post('/users',                          [UserController::class, 'store']);
            Route::put('/users/{id}',                     [UserController::class, 'update'])->whereNumber('id');
            Route::patch('/users/{id}',                   [UserController::class, 'update'])->whereNumber('id');
            Route::delete('/users/{id}',                  [UserController::class, 'destroy'])->whereNumber('id');
            Route::post('/users/{id}/assign-role',        [UserController::class, 'assignRole'])->whereNumber('id');
            Route::post('/users/{id}/revoke-role',        [UserController::class, 'revokeRole'])->whereNumber('id');
        });
    });
});
