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

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/user')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
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
