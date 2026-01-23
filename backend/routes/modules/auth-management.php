<?php

use App\Modules\AuthManagement\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1/auth')->middleware(['api'])->group(function () {
    
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('password/request-reset', [AuthController::class, 'requestPasswordReset']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('password/change', [AuthController::class, 'changePassword']);
    });
});
