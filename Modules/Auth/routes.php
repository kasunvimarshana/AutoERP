<?php
use Illuminate\Support\Facades\Route;
use Modules\Auth\Presentation\Controllers\AuthController;
Route::prefix('api/v1/auth')->middleware('api')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
