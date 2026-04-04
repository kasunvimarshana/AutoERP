<?php
use Illuminate\Support\Facades\Route;
use Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Modules\User\Infrastructure\Http\Controllers\UserController;

Route::prefix('users')->group(function () {
    Route::get('/',    [UserController::class, 'index']);
    Route::post('/',   [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::get('/{id}/profile',                  [ProfileController::class, 'show']);
    Route::patch('/{id}/profile',                [ProfileController::class, 'update']);
    Route::post('/{id}/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::patch('/{id}/profile/preferences',    [ProfileController::class, 'updatePreferences']);
    Route::post('/{id}/profile/avatar',          [ProfileController::class, 'uploadAvatar']);
});
