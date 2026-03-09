<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Service API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoints (no auth required)
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'check']);
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

// Authentication endpoints
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/validate', [AuthController::class, 'validate']);
});

// Protected auth endpoints
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
});

// User management endpoints (tenant-aware + authenticated)
Route::prefix('users')
    ->middleware(['auth:api', 'tenant'])
    ->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/{id}/roles', [UserController::class, 'assignRole']);
    });
