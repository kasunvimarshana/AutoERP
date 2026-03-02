<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Interfaces\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/auth
|
*/

Route::prefix('api/v1/auth')->name('auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:api')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('password/change', [AuthController::class, 'changePassword'])->name('password.change');
    });
});
