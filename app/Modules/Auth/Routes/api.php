<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\PlatformAuthController;
use Modules\Auth\Http\Controllers\PlatformMfaController;
use Modules\Auth\Http\Controllers\PlatformSecurityController;
use Modules\Auth\Http\Controllers\OrganizationUnitContextController;
use Modules\User\Constants\PlatformPermission;
use Modules\Auth\Http\Controllers\InitialAdministratorInvitationController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);
$authContextMiddleware = (string) config('module-auth.middleware.context_alias', 'auth.module.context');
$tokenValidationMiddleware = (string) config('module-auth.middleware.token_validation_alias', 'auth.module.token');
$ssoContextMiddleware = (string) config('module-auth.middleware.sso_context_alias', 'auth.module.sso-context');

$registerAuthRoutes = static function (string $prefix, string $namePrefix) use (
    $protectedGuard,
    $currentUserMiddleware,
    $currentTenantMiddleware,
    $currentOrganizationUnitMiddleware,
    $authContextMiddleware,
    $tokenValidationMiddleware,
    $ssoContextMiddleware,
): void {
    Route::prefix($prefix)
        ->middleware('api')
        ->name($namePrefix)
        ->group(function () use (
        $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
        $authContextMiddleware,
        $tokenValidationMiddleware,
        $ssoContextMiddleware,
    ): void {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('refresh', [AuthController::class, 'refreshToken'])->name('refresh');
        Route::post('token/exchange', [AuthController::class, 'exchangeAuthorizationCode'])->name('token.exchange');
        Route::post('token/validate', [AuthController::class, 'validateToken'])->name('token.validate');
        Route::match(['get', 'post'], 'sso/callback', [AuthController::class, 'ssoCallback'])
            ->middleware([$ssoContextMiddleware])
            ->name('sso.callback');

        Route::post(
            'verification/request',
            [AuthController::class, 'requestVerificationChallenge'],
        )->name('verification.request');
        Route::post('verification/verify', [AuthController::class, 'verifyChallenge'])->name('verification.verify');

        Route::post('organization-unit/switch', [OrganizationUnitContextController::class, 'switch'])
            ->middleware([
                'throttle:60,1',
                $tokenValidationMiddleware,
                'auth:'.$protectedGuard,
                $currentUserMiddleware,
                $currentTenantMiddleware,
                $authContextMiddleware,
            ])
            ->name('organization-unit.switch');

        Route::middleware([
            'throttle:60,1',
            $tokenValidationMiddleware,
            'auth:'.$protectedGuard,
            $currentUserMiddleware,
            $currentTenantMiddleware,
            $currentOrganizationUnitMiddleware,
            $authContextMiddleware,
        ])->group(function (): void {
            Route::post('token', [AuthController::class, 'issueToken'])->name('token.issue');
            Route::post('identities/link', [AuthController::class, 'linkExternalIdentity'])
                ->name('identities.link');
            Route::delete('identities/link', [AuthController::class, 'unlinkExternalIdentity'])
                ->name('identities.unlink');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::get('sessions', [AuthController::class, 'listSessions'])->name('sessions.list');
            Route::delete('sessions/{session}', [AuthController::class, 'revokeSession'])->name('sessions.revoke');
            Route::post('authorize-client', [AuthController::class, 'authorizeClient'])->name('client.authorize');
        });
    });
};

$registerAuthRoutes('api/v1/auth', 'api.v1.auth.');

$platformHostMiddleware = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
Route::prefix('api/v1/auth/initial-administrator')
    ->middleware(['api', $platformHostMiddleware, 'throttle:10,1'])
    ->name('api.v1.auth.initial-administrator.')
    ->group(function (): void {
        Route::post('inspect', [InitialAdministratorInvitationController::class, 'inspect'])->name('inspect');
        Route::post('accept', [InitialAdministratorInvitationController::class, 'accept'])->name('accept');
    });

$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$platformOperatorMiddleware = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');

Route::prefix('api/v1/platform/auth')
    ->middleware(['api', $platformHostMiddleware])
    ->name('api.v1.platform.auth.')
    ->group(function () use (
        $platformGuard,
        $currentUserMiddleware,
        $tokenValidationMiddleware,
        $platformOperatorMiddleware,
    ): void {
        Route::post('login', [PlatformAuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login');
        Route::post('mfa/enrollment', [PlatformMfaController::class, 'start'])
            ->middleware('throttle:5,1')
            ->name('mfa.enrollment.start');
        Route::post('mfa/enrollment/confirm', [PlatformMfaController::class, 'confirm'])
            ->middleware('throttle:5,1')
            ->name('mfa.enrollment.confirm');
        Route::post('refresh', [PlatformAuthController::class, 'refresh'])
            ->middleware('throttle:20,1')
            ->name('refresh');

        Route::middleware([
            'throttle:60,1',
            $tokenValidationMiddleware,
            'auth:'.$platformGuard,
            $currentUserMiddleware,
            $platformOperatorMiddleware,
        ])->group(function (): void {
            Route::get('me', [PlatformAuthController::class, 'me'])->name('me');
            Route::post('logout', [PlatformAuthController::class, 'logout'])->name('logout');
            Route::get('sessions', [PlatformSecurityController::class, 'sessions'])
                ->middleware('platform.permission:'.PlatformPermission::SESSIONS_VIEW)
                ->name('sessions.index');
            Route::delete('sessions/{session}', [PlatformSecurityController::class, 'revoke'])
                ->middleware([
                    (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up'),
                    'platform.permission:'.PlatformPermission::SESSIONS_MANAGE,
                ])
                ->whereUuid('session')
                ->name('sessions.revoke');
            Route::delete('operators/{operator}/sessions', [PlatformSecurityController::class, 'revokeOperatorSessions'])
                ->middleware([
                    (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up'),
                    'platform.permission:'.PlatformPermission::SESSIONS_MANAGE,
                ])
                ->whereNumber('operator')
                ->name('operators.sessions.revoke');
            Route::post('operators/{operator}/mfa/reset', [PlatformSecurityController::class, 'resetMfa'])
                ->middleware([
                    (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up'),
                    'platform.permission:'.PlatformPermission::MFA_MANAGE,
                ])
                ->whereNumber('operator')
                ->name('operators.mfa.reset');
        });
    });
