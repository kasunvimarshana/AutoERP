<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IAM\UserController;
use App\Http\Controllers\Api\IAM\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes - Auth with strict rate limiting
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('api.auth.user');
        Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('api.auth.refresh');
    });

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('api.health');

    // IAM routes
    Route::prefix('iam')->group(function () {
        // User management
        Route::apiResource('users', UserController::class);
        Route::get('users/{id}/permissions', [UserController::class, 'permissions'])->name('api.iam.users.permissions');
        Route::post('users/{id}/roles', [UserController::class, 'assignRoles'])->name('api.iam.users.assign-roles');
        
        // Role management
        Route::apiResource('roles', RoleController::class);
        Route::get('roles/{id}/users', [RoleController::class, 'users'])->name('api.iam.roles.users');
        Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions'])->name('api.iam.roles.assign-permissions');
    });
});
