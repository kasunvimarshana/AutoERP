<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module API Routes
|--------------------------------------------------------------------------
|
| Public routes (no auth required) use throttle:60,1 to limit brute-force.
| Protected routes use 'auth:api' guard (Passport).
|
*/

// Public auth endpoints — rate-limited to protect against brute-force attacks
Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);

    // Stricter limit for login (5 attempts per minute)
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // SSO token exchange
    Route::post('/sso/{provider}', [AuthController::class, 'ssoExchange']);

    // Password reset flow (public, no auth required)
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected auth endpoints
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});
