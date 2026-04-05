<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Infrastructure\Http\Controllers\PermissionController;
use Modules\Authorization\Infrastructure\Http\Controllers\RoleController;
use Modules\Authorization\Infrastructure\Http\Controllers\UserRoleController;

Route::prefix('api')->group(function () {
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/sync-permissions', [RoleController::class, 'syncPermissions']);
    Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::apiResource('permissions', PermissionController::class);
    Route::get('users/{userId}/roles', [UserRoleController::class, 'getUserRoles']);
    Route::post('users/{userId}/roles', [UserRoleController::class, 'assignRole']);
    Route::delete('users/{userId}/roles/{roleId}', [UserRoleController::class, 'removeRole']);
    Route::post('users/{userId}/roles/sync', [UserRoleController::class, 'syncRoles']);
    Route::get('users/{userId}/has-permission/{permissionSlug}', [UserRoleController::class, 'userHasPermission']);
    Route::get('users/{userId}/has-role/{roleSlug}', [UserRoleController::class, 'userHasRole']);
});
