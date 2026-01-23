<?php

use App\Modules\UserManagement\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // User routes
    Route::apiResource('users', UserController::class);
    Route::post('users/{id}/activate', [UserController::class, 'activate']);
    Route::post('users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('users/{id}/roles', [UserController::class, 'assignRoles']);
    Route::post('users/{id}/permissions', [UserController::class, 'assignPermissions']);
});
