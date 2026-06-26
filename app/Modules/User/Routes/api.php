<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\PermissionController;
use Modules\User\Http\Controllers\RoleController;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\UserDeviceController;
use Modules\User\Http\Controllers\UserDocumentController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');
$currentUserRecordMiddleware = (string) config('user.context.middleware_alias', 'current.user-record');

Route::prefix('api/v1')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
        $currentUserRecordMiddleware,
    ])
    ->name('api.v1.user.')
    ->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
        Route::patch('users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::patch('users/{user}/status', [UserController::class, 'changeStatus'])->whereNumber('user')->name('users.status');
        Route::put('users/{user}/roles', [UserController::class, 'syncRoles'])->whereNumber('user')->name('users.roles.sync');
        Route::put('users/{user}/permissions', [UserController::class, 'syncPermissions'])->whereNumber('user')->name('users.permissions.sync');
        Route::put('users/{user}/organization-access', [UserController::class, 'syncOrganizationAccess'])
            ->whereNumber('user')->name('users.organization-access.sync');
        Route::post('users/{user}/invitation/resend', [UserController::class, 'resendInvitation'])
            ->whereNumber('user')->name('users.invitation.resend');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->whereNumber('user')->name('users.destroy');

        Route::get('users/{user}/documents', [UserDocumentController::class, 'index'])->whereNumber('user')->name('users.documents.index');
        Route::post('users/{user}/documents', [UserDocumentController::class, 'store'])->whereNumber('user')->name('users.documents.store');
        Route::get('users/{user}/documents/{document}', [UserDocumentController::class, 'show'])->whereNumber('user')->whereNumber('document')->name('users.documents.show');
        Route::patch('users/{user}/documents/{document}', [UserDocumentController::class, 'update'])->whereNumber('user')->whereNumber('document')->name('users.documents.update');
        Route::delete('users/{user}/documents/{document}', [UserDocumentController::class, 'destroy'])->whereNumber('user')->whereNumber('document')->name('users.documents.destroy');
        Route::get('users/{user}/documents/{document}/download', [UserDocumentController::class, 'download'])->whereNumber('user')->whereNumber('document')->name('users.documents.download');

        Route::get('users/{user}/devices', [UserDeviceController::class, 'index'])->whereNumber('user')->name('users.devices.index');
        Route::post('users/{user}/devices', [UserDeviceController::class, 'store'])->whereNumber('user')->name('users.devices.store');
        Route::post('users/{user}/devices/{device}/touch', [UserDeviceController::class, 'touch'])->whereNumber('user')->whereNumber('device')->name('users.devices.touch');
        Route::post('users/{user}/devices/{device}/revoke', [UserDeviceController::class, 'revoke'])->whereNumber('user')->whereNumber('device')->name('users.devices.revoke');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->whereNumber('role')->name('roles.show');
        Route::patch('roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('roles.destroy');
        Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->whereNumber('role')->name('roles.permissions.sync');

        Route::get('permissions/modules', [PermissionController::class, 'modules'])->name('permissions.modules');
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('permissions/{permission}', [PermissionController::class, 'show'])->whereNumber('permission')->name('permissions.show');
    });


$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');

Route::prefix('api/v1/platform/operator-invitations')
    ->middleware(['api', $platformHost, 'throttle:10,1'])
    ->name('api.v1.platform.operator-invitations.')
    ->group(function (): void {
        Route::post('inspect', [\Modules\User\Http\Controllers\Platform\PlatformOperatorInvitationController::class, 'inspect'])
            ->name('inspect');
        Route::post('accept', [\Modules\User\Http\Controllers\Platform\PlatformOperatorInvitationController::class, 'accept'])
            ->name('accept');
    });

$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$platformStepUp = (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up');

Route::prefix('api/v1/platform/operators')
    ->middleware(['api', $platformHost, 'auth:'.$platformGuard, $currentUserMiddleware, $platformOperator])
    ->name('api.v1.platform.operators.')
    ->group(function () use ($platformStepUp): void {
        Route::get('/', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'index'])
            ->middleware('platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_VIEW)->name('index');
        Route::get('{operator}', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'show'])
            ->whereNumber('operator')->middleware('platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_VIEW)->name('show');
        Route::post('/', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'store'])
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_MANAGE])->name('store');
        Route::post('{operator}/invitation/resend', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'resendInvitation'])
            ->whereNumber('operator')->middleware([$platformStepUp, 'platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_MANAGE])->name('invitation.resend');
        Route::delete('{operator}/invitation', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'revokeInvitation'])
            ->whereNumber('operator')->middleware([$platformStepUp, 'platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_MANAGE])->name('invitation.revoke');
        Route::put('{operator}/permissions', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'permissions'])
            ->whereNumber('operator')->middleware([$platformStepUp, 'platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_MANAGE])->name('permissions');
        Route::patch('{operator}/activate', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'activate'])
            ->whereNumber('operator')->middleware([$platformStepUp, 'platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_MANAGE])->name('activate');
        Route::patch('{operator}/deactivate', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'deactivate'])
            ->whereNumber('operator')->middleware([$platformStepUp, 'platform.permission:'.\Modules\Core\Authorization\PlatformPermission::OPERATORS_MANAGE])->name('deactivate');
    });
