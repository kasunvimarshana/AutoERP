<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\TenantAuthController;
use Modules\Auth\Http\Controllers\TenantOAuthController;
use Modules\Auth\Http\Controllers\TenantSessionController;
use Modules\Auth\Http\Controllers\AuthReadinessController;
use Modules\Auth\Http\Controllers\OrganizationUnitContextController;
use Modules\Auth\Http\Controllers\PlatformAuthController;
use Modules\Auth\Http\Controllers\PlatformSecurityController;
use Modules\Core\Authorization\PlatformPermission;

$tenantGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$resolveCurrentTenant = (string) config(
    'core.current_tenant.resolver_middleware_alias',
    'resolve.current-tenant',
);
$requireCurrentTenantAccess = (string) config(
    'core.current_tenant.access_middleware_alias',
    'require.current-tenant-access',
);
$currentOrganizationUnit = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$stepUp = (string) config('module-auth.platform_step_up.middleware_alias', 'platform.step-up');

Route::prefix('api/v1/auth')
    ->middleware(['api', $resolveCurrentTenant])
    ->name('api.v1.auth.')
    ->group(function () use (
        $tenantGuard,
        $currentUser,
        $requireCurrentTenantAccess,
        $currentOrganizationUnit,
    ): void {
        Route::post('login', [TenantAuthController::class, 'login'])
            ->middleware('throttle:auth.tenant.login')
            ->name('login');
        Route::post('refresh', [TenantAuthController::class, 'refresh'])
            ->middleware('throttle:auth.tenant.refresh')
            ->name('refresh');
        Route::post('oauth/token', [TenantOAuthController::class, 'exchange'])
            ->middleware('throttle:auth.oauth.exchange')
            ->name('oauth.token');

        Route::middleware([
            'auth:'.$tenantGuard,
            $currentUser,
            $requireCurrentTenantAccess,
            $currentOrganizationUnit,
        ])->group(function (): void {
            Route::get('me', [TenantAuthController::class, 'me'])->name('me');
            Route::post('logout', [TenantAuthController::class, 'logout'])->name('logout');
            Route::get('sessions', [TenantSessionController::class, 'index'])->name('sessions.index');
            Route::delete('sessions/{session}', [TenantSessionController::class, 'revoke'])
                ->whereUuid('session')
                ->name('sessions.revoke');
            Route::post('oauth/authorize', [TenantOAuthController::class, 'authorize'])
                ->name('oauth.authorize');
            Route::post('organization-unit/switch', [OrganizationUnitContextController::class, 'switch'])
                ->name('organization-unit.switch');
        });
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
            Route::get('readiness', AuthReadinessController::class)
                ->middleware('platform.permission:'.PlatformPermission::HEALTH_VIEW)
                ->name('readiness');
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
