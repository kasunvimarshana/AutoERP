<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module API Routes
|--------------------------------------------------------------------------
|
| Public routes (no auth required) use throttle:60,1 to limit brute-force.
| Protected routes use 'auth.configured' guard (Passport).
|
*/

// Public auth endpoints — rate-limited to protect against brute-force attacks
Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('guest');

    // Stricter limit for login (5 attempts per minute)
    Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:5,1', 'guest']);

    // SSO token exchange
    Route::post('/sso/{provider}', [AuthController::class, 'ssoExchange']);

    // Password reset flow — stricter limit to prevent account enumeration and token brute-force
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

// Protected auth endpoints
Route::prefix('auth')->middleware('auth.configured')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});
