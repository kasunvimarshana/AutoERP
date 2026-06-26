<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\InitialAdministratorInvitationController;
use Modules\Auth\Http\Controllers\OrganizationUnitContextController;
use Modules\Auth\Http\Controllers\PlatformAuthController;
use Modules\Auth\Http\Controllers\PlatformMfaController;
use Modules\Auth\Http\Controllers\PlatformSecurityController;
use Modules\Core\Authorization\PlatformPermission;

$tenantGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnit = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$stepUp = (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up');

Route::prefix('api/v1/auth')
    ->middleware(['api', $currentTenant])
    ->name('api.v1.auth.')
    ->group(function () use (
        $tenantGuard,
        $currentUser,
        $currentTenant,
        $currentOrganizationUnit,
    ): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth.tenant.login')
            ->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:auth.tenant.refresh')
            ->name('refresh');
        Route::post('oauth/token', [AuthController::class, 'exchange'])
            ->middleware('throttle:auth.oauth.exchange')
            ->name('oauth.token');

        Route::middleware([
            'auth:'.$tenantGuard,
            $currentUser,
            $currentTenant,
            $currentOrganizationUnit,
        ])->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('sessions', [AuthController::class, 'sessions'])->name('sessions.index');
            Route::delete('sessions/{session}', [AuthController::class, 'revokeSession'])
                ->whereUuid('session')
                ->name('sessions.revoke');
            Route::post('oauth/authorize', [AuthController::class, 'authorize'])
                ->name('oauth.authorize');
            Route::post('organization-unit/switch', [OrganizationUnitContextController::class, 'switch'])
                ->name('organization-unit.switch');
        });
    });

Route::prefix('api/v1/auth/initial-administrator')
    ->middleware(['api', $platformHost, 'throttle:auth.invitations'])
    ->name('api.v1.auth.initial-administrator.')
    ->group(function (): void {
        Route::post('inspect', [InitialAdministratorInvitationController::class, 'inspect'])
            ->name('inspect');
        Route::post('accept', [InitialAdministratorInvitationController::class, 'accept'])
            ->name('accept');
    });

Route::prefix('api/v1/platform/auth')
    ->middleware(['api', $platformHost])
    ->name('api.v1.platform.auth.')
    ->group(function () use (
        $platformGuard,
        $currentUser,
        $platformOperator,
        $stepUp,
    ): void {
        Route::post('login', [PlatformAuthController::class, 'login'])
            ->middleware('throttle:auth.platform.login')
            ->name('login');
        Route::post('mfa/enrollment/confirm', [PlatformMfaController::class, 'confirm'])
            ->middleware('throttle:auth.invitations')
            ->name('mfa.enrollment.confirm');
        Route::post('refresh', [PlatformAuthController::class, 'refresh'])
            ->middleware('throttle:auth.platform.refresh')
            ->name('refresh');

        Route::middleware([
            'auth:'.$platformGuard,
            $currentUser,
            $platformOperator,
        ])->group(function () use ($stepUp): void {
            Route::get('me', [PlatformAuthController::class, 'me'])->name('me');
            Route::post('logout', [PlatformAuthController::class, 'logout'])->name('logout');
            Route::get('sessions', [PlatformSecurityController::class, 'sessions'])
                ->middleware('platform.permission:'.PlatformPermission::SESSIONS_VIEW)
                ->name('sessions.index');
            Route::delete('sessions/{session}', [PlatformSecurityController::class, 'revoke'])
                ->middleware([
                    $stepUp,
                    'platform.permission:'.PlatformPermission::SESSIONS_MANAGE,
                ])
                ->whereUuid('session')
                ->name('sessions.revoke');
            Route::delete(
                'operators/{operator}/sessions',
                [PlatformSecurityController::class, 'revokeOperatorSessions'],
            )
                ->middleware([
                    $stepUp,
                    'platform.permission:'.PlatformPermission::SESSIONS_MANAGE,
                ])
                ->whereNumber('operator')
                ->name('operators.sessions.revoke');
        });
    });
