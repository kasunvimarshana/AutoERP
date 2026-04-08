<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Infrastructure\Http\Controllers\PermissionController;
use Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Modules\User\Infrastructure\Http\Controllers\RoleController;
use Modules\User\Infrastructure\Http\Controllers\UserAttachmentController;
use Modules\User\Infrastructure\Http\Controllers\UserController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    // Profile endpoints (authenticated user managing their own profile)
    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::post('profile/change-password', [ProfileController::class, 'changePassword']);
    Route::patch('profile/preferences', [ProfileController::class, 'updatePreferences']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);

    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::patch('users/{user}/preferences', [UserController::class, 'updatePreferences']);

    Route::get('users/{user}/attachments', [UserAttachmentController::class, 'index']);
    Route::post('users/{user}/attachments', [UserAttachmentController::class, 'store']);
    Route::delete('users/{user}/attachments/{attachment}', [UserAttachmentController::class, 'destroy']);

    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{role}', [RoleController::class, 'show']);
    Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::get('permissions/{permission}', [PermissionController::class, 'show']);
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/user-attachments/{uuid}', [UserAttachmentController::class, 'serve'])->middleware('auth:api');
