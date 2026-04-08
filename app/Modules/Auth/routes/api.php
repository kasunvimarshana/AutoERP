<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\AuthController;
use Modules\Auth\Infrastructure\Http\Controllers\RoleController;
use Modules\Auth\Infrastructure\Http\Controllers\UserController;

Route::prefix('api')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('auth/me', [AuthController::class, 'me'])->middleware('auth:api');

    Route::middleware(['auth:api'])->group(function (): void {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });
});
