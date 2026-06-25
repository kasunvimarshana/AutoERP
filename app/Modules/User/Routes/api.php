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
        Route::apiResource('permissions', PermissionController::class)->only(['index', 'show']);
        Route::apiResource('user-documents', UserDocumentController::class);
        Route::apiResource('user-devices', UserDeviceController::class);
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
    ->middleware([
        'api',
        $platformHost,
        'auth:'.$platformGuard,
        $currentUserMiddleware,
        $platformOperator,
    ])
    ->name('api.v1.platform.operators.')
    ->group(function () use ($platformStepUp): void {
        Route::get('/', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'index'])
            ->middleware('platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_VIEW)
            ->name('index');
        Route::get('{operator}', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'show'])
            ->whereNumber('operator')
            ->middleware('platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_VIEW)
            ->name('show');
        Route::post('/', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'store'])
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_MANAGE])
            ->name('store');
        Route::post('{operator}/invitation/resend', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'resendInvitation'])
            ->whereNumber('operator')
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_MANAGE])
            ->name('invitation.resend');
        Route::delete('{operator}/invitation', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'revokeInvitation'])
            ->whereNumber('operator')
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_MANAGE])
            ->name('invitation.revoke');
        Route::put('{operator}/permissions', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'permissions'])
            ->whereNumber('operator')
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_MANAGE])
            ->name('permissions');
        Route::patch('{operator}/activate', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'activate'])
            ->whereNumber('operator')
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_MANAGE])
            ->name('activate');
        Route::patch('{operator}/deactivate', [\Modules\User\Http\Controllers\Platform\PlatformOperatorController::class, 'deactivate'])
            ->whereNumber('operator')
            ->middleware([$platformStepUp, 'platform.permission:'.\Modules\User\Constants\PlatformPermission::OPERATORS_MANAGE])
            ->name('deactivate');
    });
