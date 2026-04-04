<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Infrastructure\Http\Controllers\PermissionController;
use Modules\Authorization\Infrastructure\Http\Controllers\RoleController;
use Modules\Authorization\Infrastructure\Http\Controllers\UserRoleController;

Route::prefix('roles')->group(function () {
    Route::get('/',                        [RoleController::class, 'index']);
    Route::post('/',                       [RoleController::class, 'store']);
    Route::get('/{id}',                    [RoleController::class, 'show']);
    Route::delete('/{id}',                 [RoleController::class, 'destroy']);
    Route::put('/{id}/permissions',        [RoleController::class, 'syncPermissions']);
});

Route::prefix('permissions')->group(function () {
    Route::get('/',     [PermissionController::class, 'index']);
    Route::post('/',    [PermissionController::class, 'store']);
    Route::get('/{id}', [PermissionController::class, 'show']);
    Route::delete('/{id}', [PermissionController::class, 'destroy']);
});

Route::prefix('users')->group(function () {
    Route::post('/{userId}/assign-role', [UserRoleController::class, 'assign']);
});
