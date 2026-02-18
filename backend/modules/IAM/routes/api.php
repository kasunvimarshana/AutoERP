<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Controllers\AuthController;
use Modules\IAM\Http\Controllers\PermissionController;
use Modules\IAM\Http\Controllers\RoleController;
use Modules\IAM\Http\Controllers\UserController;
use Modules\IAM\Http\Middleware\Authenticate;
use Modules\IAM\Http\Middleware\CheckPermission;
use Modules\IAM\Http\Middleware\CheckRole;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::middleware(['auth:sanctum', Authenticate::class])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // User routes
    Route::prefix('users')->group(function () {
        // Profile routes (no permission required for own profile)
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/change-password', [UserController::class, 'changePassword']);

        // User management routes (require permissions)
        Route::middleware([CheckPermission::class.':user.view'])->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/search', [UserController::class, 'search']);
            Route::get('/{id}', [UserController::class, 'show']);
        });

        Route::post('/', [UserController::class, 'store'])
            ->middleware(CheckPermission::class.':user.create');

        Route::put('/{id}', [UserController::class, 'update'])
            ->middleware(CheckPermission::class.':user.update');

        Route::delete('/{id}', [UserController::class, 'destroy'])
            ->middleware(CheckPermission::class.':user.delete');

        Route::post('/{id}/activate', [UserController::class, 'activate'])
            ->middleware(CheckPermission::class.':user.update');

        Route::post('/{id}/deactivate', [UserController::class, 'deactivate'])
            ->middleware(CheckPermission::class.':user.update');

        Route::post('/{id}/roles/assign', [UserController::class, 'assignRole'])
            ->middleware(CheckPermission::class.':user.assign-role');

        Route::post('/{id}/roles/remove', [UserController::class, 'removeRole'])
            ->middleware(CheckPermission::class.':user.assign-role');

        Route::post('/{id}/roles/sync', [UserController::class, 'syncRoles'])
            ->middleware(CheckPermission::class.':user.assign-role');
    });

    // Role routes
    Route::prefix('roles')->middleware([CheckRole::class.':admin,super-admin'])->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->middleware(CheckPermission::class.':role.view');

        Route::get('/hierarchy', [RoleController::class, 'hierarchy'])
            ->middleware(CheckPermission::class.':role.view');

        Route::get('/{id}', [RoleController::class, 'show'])
            ->middleware(CheckPermission::class.':role.view');

        Route::get('/{id}/permissions', [RoleController::class, 'permissions'])
            ->middleware(CheckPermission::class.':role.view');

        Route::post('/', [RoleController::class, 'store'])
            ->middleware(CheckPermission::class.':role.create');

        Route::put('/{id}', [RoleController::class, 'update'])
            ->middleware(CheckPermission::class.':role.update');

        Route::delete('/{id}', [RoleController::class, 'destroy'])
            ->middleware(CheckPermission::class.':role.delete');

        Route::post('/{id}/permissions/assign', [RoleController::class, 'assignPermissions'])
            ->middleware(CheckPermission::class.':role.assign-permission');

        Route::post('/{id}/permissions/revoke', [RoleController::class, 'revokePermissions'])
            ->middleware(CheckPermission::class.':role.assign-permission');

        Route::post('/{id}/permissions/sync', [RoleController::class, 'syncPermissions'])
            ->middleware(CheckPermission::class.':role.assign-permission');
    });

    // Permission routes
    Route::prefix('permissions')->middleware([CheckRole::class.':admin,super-admin'])->group(function () {
        Route::get('/', [PermissionController::class, 'index'])
            ->middleware(CheckPermission::class.':permission.view');

        Route::get('/grouped', [PermissionController::class, 'grouped'])
            ->middleware(CheckPermission::class.':permission.view');

        Route::get('/by-resource', [PermissionController::class, 'byResource'])
            ->middleware(CheckPermission::class.':permission.view');

        Route::get('/{id}', [PermissionController::class, 'show'])
            ->middleware(CheckPermission::class.':permission.view');

        Route::post('/', [PermissionController::class, 'store'])
            ->middleware(CheckPermission::class.':permission.create');

        Route::post('/bulk', [PermissionController::class, 'createBulk'])
            ->middleware(CheckPermission::class.':permission.create');

        Route::delete('/{id}', [PermissionController::class, 'destroy'])
            ->middleware(CheckPermission::class.':permission.delete');
    });
});
