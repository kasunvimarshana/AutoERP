<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Presentation\Http\Controllers\AuthController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/auth')
    ->middleware('api')
    ->name('auth.')
    ->group(function () use (
        $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ): void {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('token/refresh', [AuthController::class, 'refreshToken'])->name('token.refresh');
        Route::post('token/exchange', [AuthController::class, 'exchangeAuthorizationCode'])->name('token.exchange');
        Route::post('token/validate', [AuthController::class, 'validateToken'])->name('token.validate');

        Route::post(
            'verification/request',
            [AuthController::class, 'requestVerificationChallenge'],
        )->name('verification.request');
        Route::post('verification/verify', [AuthController::class, 'verifyChallenge'])->name('verification.verify');

        Route::middleware([
            'auth:' . $protectedGuard,
            $currentUserMiddleware,
            $currentTenantMiddleware,
            $currentOrganizationUnitMiddleware,
        ])->group(function (): void {
            Route::post('token', [AuthController::class, 'issueToken'])->name('token.issue');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('sessions', [AuthController::class, 'listSessions'])->name('sessions.list');
            Route::delete('sessions/{session}', [AuthController::class, 'revokeSession'])->name('sessions.revoke');
            Route::post('authorize-client', [AuthController::class, 'authorizeClient'])->name('client.authorize');
        });
    });
