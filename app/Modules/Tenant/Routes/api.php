<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\TenantController;
use Modules\Tenant\Http\Controllers\TenantDocumentController;
use Modules\Tenant\Http\Controllers\TenantDomainController;
use Modules\Tenant\Http\Controllers\TenantPlanController;
use Modules\Tenant\Http\Controllers\TenantOnboardingController;
use Modules\Tenant\Http\Controllers\PlatformTenantDomainController;
use Modules\Tenant\Http\Controllers\TenantProfileController;
use Modules\Tenant\Http\Controllers\TenantSubscriptionController;
use Modules\Tenant\Constants\PlatformPermission;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$platformHostMiddleware = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperatorMiddleware = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$platformStepUpMiddleware = (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up');

$authenticatedMiddleware = [
    'api',
    'auth:'.$protectedGuard,
    $currentUserMiddleware,
];

$authenticatedTenantMiddleware = [
    ...$authenticatedMiddleware,
    $currentTenantMiddleware,
];

$platformMiddleware = [
    'api',
    $platformHostMiddleware,
    'auth:'.$platformGuard,
    $currentUserMiddleware,
    $platformOperatorMiddleware,
];

Route::prefix('api/v1/platform')
    ->middleware($platformMiddleware)
    ->name('platform.')
    ->group(function () use ($platformStepUpMiddleware) : void {
        Route::get('tenants', [TenantController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->name('tenants.index');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->name('tenants.show');
        Route::post('tenants', [TenantController::class, 'store'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_CREATE])
            ->name('tenants.store');
        Route::match(['put', 'patch'], 'tenants/{tenant}', [TenantController::class, 'update'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_UPDATE])
            ->name('tenants.update');

        foreach (['activate', 'suspend', 'deactivate', 'archive'] as $action) {
            Route::patch("tenants/{tenant}/{$action}", [TenantController::class, $action])
                ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_LIFECYCLE])
                ->name("tenants.{$action}");
        }

        Route::post('tenants/{tenant}/onboarding/provision', [TenantOnboardingController::class, 'provision'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_ONBOARD])
            ->name('tenants.onboarding.provision');
        Route::get('tenants/{tenant}/onboarding/readiness', [TenantOnboardingController::class, 'readiness'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->name('tenants.onboarding.readiness');

        Route::get('tenants/{tenant}/subscription', [TenantSubscriptionController::class, 'current'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->name('tenants.subscription.current');
        Route::get(
            'tenants/{tenant}/subscription/readiness/{tenantPlanRevision}',
            [TenantSubscriptionController::class, 'readiness'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE])
            ->name('tenants.subscription.readiness');
        Route::put('tenants/{tenant}/subscription', [TenantSubscriptionController::class, 'assign'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE])
            ->name('tenants.subscription.assign');

        Route::get('tenants/{tenant}/domains', [PlatformTenantDomainController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->name('tenants.domains.index');
        Route::post('tenants/{tenant}/domains', [PlatformTenantDomainController::class, 'store'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->name('tenants.domains.store');
        Route::post(
            'tenants/{tenant}/domains/{tenantDomain}/verification-challenge',
            [PlatformTenantDomainController::class, 'requestVerification'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->name('tenants.domains.challenge');
        Route::post(
            'tenants/{tenant}/domains/{tenantDomain}/verify',
            [PlatformTenantDomainController::class, 'verify'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->name('tenants.domains.verify');
        Route::patch(
            'tenants/{tenant}/domains/{tenantDomain}/primary',
            [PlatformTenantDomainController::class, 'setPrimary'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->name('tenants.domains.primary');
        Route::patch(
            'tenants/{tenant}/domains/{tenantDomain}/disable',
            [PlatformTenantDomainController::class, 'disable'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->name('tenants.domains.disable');
        Route::delete(
            'tenants/{tenant}/domains/{tenantDomain}',
            [PlatformTenantDomainController::class, 'destroy'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->name('tenants.domains.destroy');

        Route::get('tenant-plans', [TenantPlanController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->name('tenant-plans.index');
        Route::get('tenant-plans/{tenantPlan}', [TenantPlanController::class, 'show'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->name('tenant-plans.show');
        Route::get('tenant-plans/{tenantPlan}/revisions', [TenantPlanController::class, 'revisions'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->name('tenant-plans.revisions');
        Route::post('tenant-plans', [TenantPlanController::class, 'store'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->name('tenant-plans.store');
        Route::match(['put', 'patch'], 'tenant-plans/{tenantPlan}', [TenantPlanController::class, 'update'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->name('tenant-plans.update');
        Route::patch('tenant-plans/{tenantPlan}/deactivate', [TenantPlanController::class, 'deactivate'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->name('tenant-plans.deactivate');
        Route::patch('tenant-plans/{tenantPlan}/activate', [TenantPlanController::class, 'activate'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->name('tenant-plans.activate');
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
