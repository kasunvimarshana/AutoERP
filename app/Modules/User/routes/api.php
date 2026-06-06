<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\PermissionController;
use Modules\User\Http\Controllers\RoleController;
use Modules\User\Http\Controllers\RolePermissionController;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\UserDeviceController;
use Modules\User\Http\Controllers\UserDocumentController;
use Modules\User\Http\Controllers\UserPermissionController;
use Modules\User\Http\Controllers\UserRoleController;
use Modules\User\Http\Controllers\UserTenantController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);
$currentUserRecordMiddleware = (string) config('user.context.middleware_alias', 'current.user-record');

Route::prefix('api/user')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
        $currentUserRecordMiddleware,
    ])
    ->name('user.')
    ->group(function (): void {
        Route::get('users/resolve-identity', [UserController::class, 'resolveByIdentity'])
            ->name('users.resolve-identity');
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])
            ->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])
            ->name('users.deactivate');
        Route::patch('users/{user}/suspend', [UserController::class, 'suspend'])
            ->name('users.suspend');
        Route::post('users/{user}/organization-units', [UserController::class, 'assignOrganizationUnit'])
            ->name('users.organization-units.assign');
        Route::delete(
            'users/{user}/organization-units/{organizationUnit}',
            [UserController::class, 'removeOrganizationUnit'],
        )
            ->name('users.organization-units.remove');

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
