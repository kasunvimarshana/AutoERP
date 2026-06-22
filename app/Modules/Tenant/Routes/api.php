<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\TenantController;
use Modules\Tenant\Http\Controllers\TenantDocumentController;
use Modules\Tenant\Http\Controllers\TenantDomainController;
use Modules\Tenant\Http\Controllers\TenantPlanController;
use Modules\Tenant\Http\Controllers\TenantProfileController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');

$authenticatedTenantMiddleware = [
    'api',
    'auth:'.$protectedGuard,
    $currentUserMiddleware,
    $currentTenantMiddleware,
];

Route::prefix('api/v1/platform')
    ->middleware($authenticatedTenantMiddleware)
    ->name('platform.')
    ->group(function (): void {
        Route::apiResource('tenants', TenantController::class)
            ->only(['index', 'show', 'store', 'update']);
        Route::patch('tenants/{tenant}/activate', [TenantController::class, 'activate'])
            ->name('tenants.activate');
        Route::patch('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])
            ->name('tenants.suspend');
        Route::patch('tenants/{tenant}/deactivate', [TenantController::class, 'deactivate'])
            ->name('tenants.deactivate');
        Route::patch('tenants/{tenant}/archive', [TenantController::class, 'archive'])
            ->name('tenants.archive');

        Route::apiResource('tenant-plans', TenantPlanController::class)
            ->only(['index', 'show', 'store', 'update']);
        Route::patch(
            'tenant-plans/{tenantPlan}/deactivate',
            [TenantPlanController::class, 'deactivate'],
        )->name('tenant-plans.deactivate');
    });

Route::prefix('api/v1/tenant')
    ->middleware($authenticatedTenantMiddleware)
    ->name('tenant.')
    ->group(function (): void {
        Route::get('profile', [TenantProfileController::class, 'show'])
            ->name('profile.show');
        Route::patch('profile', [TenantProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('domains', [TenantDomainController::class, 'index'])
            ->name('domains.index');
        Route::post('domains', [TenantDomainController::class, 'store'])
            ->name('domains.store');
        Route::get('domains/{tenantDomain}', [TenantDomainController::class, 'show'])
            ->name('domains.show');
        Route::post(
            'domains/{tenantDomain}/verification-challenge',
            [TenantDomainController::class, 'requestVerification'],
        )->name('domains.challenge');
        Route::post('domains/{tenantDomain}/verify', [TenantDomainController::class, 'verify'])
            ->name('domains.verify');
        Route::patch(
            'domains/{tenantDomain}/primary',
            [TenantDomainController::class, 'setPrimary'],
        )->name('domains.primary');
        Route::patch(
            'domains/{tenantDomain}/disable',
            [TenantDomainController::class, 'disable'],
        )->name('domains.disable');
        Route::delete('domains/{tenantDomain}', [TenantDomainController::class, 'destroy'])
            ->name('domains.destroy');

        Route::get('documents', [TenantDocumentController::class, 'index'])
            ->name('documents.index');
        Route::post('documents', [TenantDocumentController::class, 'store'])
            ->name('documents.store');
        Route::get('documents/{tenantDocument}', [TenantDocumentController::class, 'show'])
            ->name('documents.show');
        Route::patch('documents/{tenantDocument}', [TenantDocumentController::class, 'update'])
            ->name('documents.update');
        Route::delete('documents/{tenantDocument}', [TenantDocumentController::class, 'destroy'])
            ->name('documents.destroy');
        Route::get(
            'documents/{tenantDocument}/download',
            [TenantDocumentController::class, 'download'],
        )->name('documents.download');
    });
