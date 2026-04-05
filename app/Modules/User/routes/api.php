<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Modules\User\Infrastructure\Http\Controllers\UserController;

Route::prefix('api')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::prefix('users/{id}')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::post('profile/change-password', [ProfileController::class, 'changePassword']);
        Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    });
});
