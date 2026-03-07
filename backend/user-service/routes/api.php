<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'user-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->middleware(['auth.passport', 'tenant'])->group(function () {
    // User CRUD
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{id}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::patch('/users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::patch('/users/{id}/attributes', [UserController::class, 'updateAttributes']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{name}', [RoleController::class, 'show']);

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index']);
});
