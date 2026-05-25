<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Presentation\Http\Controllers\PermissionController;
use Modules\User\Presentation\Http\Controllers\RoleController;
use Modules\User\Presentation\Http\Controllers\RolePermissionController;
use Modules\User\Presentation\Http\Controllers\UserController;
use Modules\User\Presentation\Http\Controllers\UserDeviceController;
use Modules\User\Presentation\Http\Controllers\UserDocumentController;
use Modules\User\Presentation\Http\Controllers\UserPermissionController;
use Modules\User\Presentation\Http\Controllers\UserRoleController;
use Modules\User\Presentation\Http\Controllers\UserTenantController;

Route::prefix('api/user')
    ->middleware('api')
    ->name('user.')
    ->group(function (): void {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);
        Route::apiResource('role-permissions', RolePermissionController::class);
        Route::apiResource('user-roles', UserRoleController::class);
        Route::apiResource('user-permissions', UserPermissionController::class);
        Route::apiResource('user-tenants', UserTenantController::class);
        Route::apiResource('user-documents', UserDocumentController::class);
        Route::apiResource('user-devices', UserDeviceController::class);
    });
