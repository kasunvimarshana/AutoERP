<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Controllers\AuthController;
use Modules\IAM\Http\Controllers\GroupController;
use Modules\IAM\Http\Controllers\PermissionController;
use Modules\IAM\Http\Controllers\RoleController;
use Modules\IAM\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - IAM Module
|--------------------------------------------------------------------------
*/

// Authentication routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/password/reset', [AuthController::class, 'initiatePasswordReset']);
    Route::post('/password/reset/confirm', [AuthController::class, 'resetPassword']);
    
    // Protected auth routes
    Route::middleware(['jwt.auth'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// IAM routes (protected)
Route::prefix('v1/iam')->middleware(['jwt.auth'])->group(function () {

    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/search', [UserController::class, 'search']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);

        // User-specific actions
        Route::post('/{user}/activate', [UserController::class, 'activate']);
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/{user}/roles', [UserController::class, 'assignRoles']);
        Route::post('/{user}/permissions', [UserController::class, 'assignPermissions']);
        Route::get('/{user}/permissions', [UserController::class, 'permissions']);
    });

    // Role routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{role}', [RoleController::class, 'show']);
        Route::put('/{role}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);

        // Role-specific actions
        Route::post('/{role}/permissions', [RoleController::class, 'assignPermissions']);
        Route::get('/{role}/permissions', [RoleController::class, 'permissions']);
        Route::get('/{role}/users', [RoleController::class, 'users']);
    });

    // Permission routes
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('/active', [PermissionController::class, 'active']);
        Route::get('/search', [PermissionController::class, 'search']);
        Route::get('/module/{module}', [PermissionController::class, 'byModule']);
        Route::post('/generate-crud', [PermissionController::class, 'generateCrud']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);

        // Assign permissions
        Route::post('/assign/role/{roleId}', [PermissionController::class, 'assignToRole']);
        Route::post('/assign/user/{userId}', [PermissionController::class, 'assignToUser']);
    });

    // Group routes
    Route::prefix('groups')->group(function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store']);
        Route::get('/active', [GroupController::class, 'active']);
        Route::get('/tree', [GroupController::class, 'tree']);
        Route::get('/roots', [GroupController::class, 'roots']);
        Route::get('/{group}', [GroupController::class, 'show']);
        Route::put('/{group}', [GroupController::class, 'update']);
        Route::delete('/{group}', [GroupController::class, 'destroy']);

        // Group-specific actions
        Route::post('/{group}/users', [GroupController::class, 'addUser']);
        Route::delete('/{group}/users', [GroupController::class, 'removeUser']);
        Route::post('/{group}/roles', [GroupController::class, 'assignRole']);
        Route::delete('/{group}/roles', [GroupController::class, 'removeRole']);
    });
});
