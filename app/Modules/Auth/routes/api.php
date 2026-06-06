<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

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

Route::prefix('api/auth')
    ->middleware('api')
    ->name('auth.')
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
        Route::post('token/refresh', [AuthController::class, 'refreshToken'])->name('token.refresh');
        Route::post('token/exchange', [AuthController::class, 'exchangeAuthorizationCode'])->name('token.exchange');
        Route::post('validate', [AuthController::class, 'validateToken'])->name('validate');
        Route::post('token/validate', [AuthController::class, 'validateToken'])->name('token.validate');
        Route::match(['get', 'post'], 'sso/callback', [AuthController::class, 'ssoCallback'])
            ->middleware([$ssoContextMiddleware])
            ->name('sso.callback');

        Route::post(
            'verification/request',
            [AuthController::class, 'requestVerificationChallenge'],
        )->name('verification.request');
        Route::post('verification/verify', [AuthController::class, 'verifyChallenge'])->name('verification.verify');

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
