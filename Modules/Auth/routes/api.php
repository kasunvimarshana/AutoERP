<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

/*
 * API Routes for Authentication Module
 */

// Public routes (no authentication required)
Route::prefix('api/v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify'); // Laravel expects this route name for MustVerifyEmail
});

// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->prefix('api/v1/auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
    Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::post('resend-verification', [AuthController::class, 'resendEmailVerification'])->name('auth.resend-verification');
});
