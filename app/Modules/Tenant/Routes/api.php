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
use Modules\Tenant\Http\Controllers\TenantSubscriptionPlanController;
use Modules\Tenant\Http\Controllers\Platform\PlatformTenantHealthController;
use Modules\Tenant\Http\Controllers\Platform\PlatformTenantTargetController;
use Modules\Core\Authorization\PlatformPermission;
use Modules\Tenant\Constants\TenantDomainProbe;
use Modules\Tenant\Http\Controllers\Public\TenantDomainProbeController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$platformHostMiddleware = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperatorMiddleware = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$platformStepUpMiddleware = (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up');
$domainVerificationThrottle = 'throttle:'.max(1, (int) config('tenant.domains.verification_rate_limit_per_minute', 10)).',1';
$domainProbeThrottle = 'throttle:'.max(1, (int) config('tenant.domains.probe_rate_limit_per_minute', 120)).',1';

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


Route::get(TenantDomainProbe::PATH, TenantDomainProbeController::class)
    ->middleware(['api', $domainProbeThrottle])
    ->name('tenant-domain.probe');

Route::prefix('api/v1/platform')
    ->middleware($platformMiddleware)
    ->name('platform.')
    ->group(function () use ($platformStepUpMiddleware, $domainVerificationThrottle) : void {
        foreach ([
            'configuration-targets' => PlatformPermission::CONFIGURATION_VIEW,
            'audit-targets' => PlatformPermission::AUDIT_VIEW,
            'health-targets' => PlatformPermission::HEALTH_VIEW,
        ] as $targetPath => $targetPermission) {
            Route::get("{$targetPath}/tenants", [PlatformTenantTargetController::class, 'index'])
                ->middleware('platform.permission:'.$targetPermission)
                ->name("{$targetPath}.tenants.index");
            Route::get("{$targetPath}/tenants/{tenant}", [PlatformTenantTargetController::class, 'show'])
                ->middleware('platform.permission:'.$targetPermission)
                ->whereNumber('tenant')
                ->name("{$targetPath}.tenants.show");
        }

        Route::get('tenants', [TenantController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->name('tenants.index');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->whereNumber('tenant')
            ->name('tenants.show');
        Route::post('tenants', [TenantController::class, 'store'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_CREATE])
            ->name('tenants.store');
        Route::match(['put', 'patch'], 'tenants/{tenant}', [TenantController::class, 'update'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_UPDATE])
            ->whereNumber('tenant')
            ->name('tenants.update');

        foreach (['activate', 'suspend', 'deactivate', 'archive'] as $action) {
            Route::patch("tenants/{tenant}/{$action}", [TenantController::class, $action])
                ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_LIFECYCLE])
                ->whereNumber('tenant')
                ->name("tenants.{$action}");
        }

        Route::post('tenants/{tenant}/onboarding/provision', [TenantOnboardingController::class, 'provision'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_ONBOARD])
            ->whereNumber('tenant')
            ->name('tenants.onboarding.provision');
        Route::get('tenants/{tenant}/onboarding/readiness', [TenantOnboardingController::class, 'readiness'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->whereNumber('tenant')
            ->name('tenants.onboarding.readiness');

        Route::get('tenants/{tenant}/onboarding/initial-administrator-invitation', [TenantOnboardingController::class, 'invitation'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->whereNumber('tenant')
            ->name('tenants.onboarding.invitation.show');
        Route::post(
            'tenants/{tenant}/onboarding/initial-administrator-invitations/{invitation}/resend',
            [TenantOnboardingController::class, 'resendInvitation'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_ONBOARD])
            ->whereNumber('tenant')
            ->whereNumber('invitation')
            ->name('tenants.onboarding.invitation.resend');
        Route::post(
            'tenants/{tenant}/onboarding/initial-administrator-invitations/{invitation}/revoke',
            [TenantOnboardingController::class, 'revokeInvitation'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_ONBOARD])
            ->whereNumber('tenant')
            ->whereNumber('invitation')
            ->name('tenants.onboarding.invitation.revoke');
        Route::post(
            'tenants/{tenant}/onboarding/initial-administrator-invitations/{invitation}/replace',
            [TenantOnboardingController::class, 'replaceInvitation'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANTS_ONBOARD])
            ->whereNumber('tenant')
            ->whereNumber('invitation')
            ->name('tenants.onboarding.invitation.replace');

        Route::get('subscription-plans', [TenantSubscriptionPlanController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE)
            ->name('subscription-plans.index');
        Route::get('subscription-plans/{tenantPlan}', [TenantSubscriptionPlanController::class, 'show'])
            ->middleware('platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE)
            ->whereNumber('tenantPlan')
            ->name('subscription-plans.show');
        Route::get('subscription-plans/{tenantPlan}/revisions', [TenantSubscriptionPlanController::class, 'revisions'])
            ->middleware('platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE)
            ->whereNumber('tenantPlan')
            ->name('subscription-plans.revisions');

        Route::get('tenants/{tenant}/subscription', [TenantSubscriptionController::class, 'current'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->whereNumber('tenant')
            ->name('tenants.subscription.current');
        Route::get('tenants/{tenant}/subscription/history', [TenantSubscriptionController::class, 'history'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->whereNumber('tenant')
            ->name('tenants.subscription.history');
        Route::get(
            'tenants/{tenant}/subscription/readiness/{tenantPlanRevision}',
            [TenantSubscriptionController::class, 'readiness'],
        )->middleware('platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE)
            ->whereNumber('tenant')
            ->whereNumber('tenantPlanRevision')
            ->name('tenants.subscription.readiness');
        foreach (['assign', 'renew', 'extend', 'correct', 'cancel'] as $subscriptionAction) {
            Route::post(
                "tenants/{tenant}/subscription/{$subscriptionAction}",
                [TenantSubscriptionController::class, $subscriptionAction],
            )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_SUBSCRIPTIONS_MANAGE])
                ->whereNumber('tenant')
                ->name("tenants.subscription.{$subscriptionAction}");
        }

        Route::get('tenants/{tenant}/domains', [PlatformTenantDomainController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::TENANTS_VIEW)
            ->whereNumber('tenant')
            ->name('tenants.domains.index');
        Route::post('tenants/{tenant}/domains', [PlatformTenantDomainController::class, 'store'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->whereNumber('tenant')
            ->name('tenants.domains.store');
        Route::post(
            'tenants/{tenant}/domains/{tenantDomain}/verification-challenge',
            [PlatformTenantDomainController::class, 'requestVerification'],
        )->middleware([$platformStepUpMiddleware, $domainVerificationThrottle, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->whereNumber('tenant')
            ->whereNumber('tenantDomain')
            ->name('tenants.domains.challenge');
        Route::post(
            'tenants/{tenant}/domains/{tenantDomain}/verify',
            [PlatformTenantDomainController::class, 'verify'],
        )->middleware([$platformStepUpMiddleware, $domainVerificationThrottle, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->whereNumber('tenant')
            ->whereNumber('tenantDomain')
            ->name('tenants.domains.verify');
        Route::patch(
            'tenants/{tenant}/domains/{tenantDomain}/primary',
            [PlatformTenantDomainController::class, 'setPrimary'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->whereNumber('tenant')
            ->whereNumber('tenantDomain')
            ->name('tenants.domains.primary');
        Route::patch(
            'tenants/{tenant}/domains/{tenantDomain}/disable',
            [PlatformTenantDomainController::class, 'disable'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->whereNumber('tenant')
            ->whereNumber('tenantDomain')
            ->name('tenants.domains.disable');
        Route::delete(
            'tenants/{tenant}/domains/{tenantDomain}',
            [PlatformTenantDomainController::class, 'destroy'],
        )->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::TENANT_DOMAINS_MANAGE])
            ->whereNumber('tenant')
            ->whereNumber('tenantDomain')
            ->name('tenants.domains.destroy');

        Route::get('tenant-plans/capabilities', [TenantPlanController::class, 'capabilities'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->name('tenant-plans.capabilities');
        Route::get('tenant-plans', [TenantPlanController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->name('tenant-plans.index');
        Route::get('tenant-plans/{tenantPlan}/tenants', [TenantPlanController::class, 'assignments'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->whereNumber('tenantPlan')
            ->name('tenant-plans.assignments');
        Route::get('tenant-plans/{tenantPlan}', [TenantPlanController::class, 'show'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->whereNumber('tenantPlan')
            ->name('tenant-plans.show');
        Route::get('tenant-plans/{tenantPlan}/revisions', [TenantPlanController::class, 'revisions'])
            ->middleware('platform.permission:'.PlatformPermission::PLANS_VIEW)
            ->whereNumber('tenantPlan')
            ->name('tenant-plans.revisions');
        Route::post('tenant-plans', [TenantPlanController::class, 'store'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->name('tenant-plans.store');
        Route::match(['put', 'patch'], 'tenant-plans/{tenantPlan}', [TenantPlanController::class, 'update'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->whereNumber('tenantPlan')
            ->name('tenant-plans.update');
        Route::patch('tenant-plans/{tenantPlan}/deactivate', [TenantPlanController::class, 'deactivate'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->whereNumber('tenantPlan')
            ->name('tenant-plans.deactivate');
        Route::patch('tenant-plans/{tenantPlan}/activate', [TenantPlanController::class, 'activate'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::PLANS_MANAGE])
            ->whereNumber('tenantPlan')
            ->name('tenant-plans.activate');


        Route::get('health', [PlatformTenantHealthController::class, 'index'])
            ->middleware('platform.permission:'.PlatformPermission::HEALTH_VIEW)
            ->name('health.index');
        Route::get('health/tenants/{tenant}', [PlatformTenantHealthController::class, 'tenant'])
            ->middleware('platform.permission:'.PlatformPermission::HEALTH_VIEW)
            ->whereNumber('tenant')
            ->name('health.tenants.show');
        Route::post('health/domains/retry-failed', [PlatformTenantHealthController::class, 'retryDomains'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::HEALTH_MANAGE])
            ->name('health.domains.retry-failed');
        Route::post('health/outbox/{eventUuid}/retry', [PlatformTenantHealthController::class, 'retryOutbox'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::HEALTH_MANAGE])
            ->whereUuid('eventUuid')
            ->name('health.outbox.retry');
        Route::post('health/storage-cleanups/{job}/retry', [PlatformTenantHealthController::class, 'retryStorage'])
            ->middleware([$platformStepUpMiddleware, 'platform.permission:'.PlatformPermission::HEALTH_MANAGE])
            ->whereNumber('job')
            ->name('health.storage.retry');
    });

Route::prefix('api/v1/tenant')
    ->middleware($authenticatedTenantMiddleware)
    ->name('tenant.')
    ->group(function () use ($domainVerificationThrottle): void {
        Route::get('profile', [TenantProfileController::class, 'show'])
            ->name('profile.show');
        Route::patch('profile', [TenantProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('domains', [TenantDomainController::class, 'index'])
            ->name('domains.index');
        Route::post('domains', [TenantDomainController::class, 'store'])
            ->name('domains.store');
        Route::get('domains/{tenantDomain}', [TenantDomainController::class, 'show'])
            ->whereNumber('tenantDomain')
            ->name('domains.show');
        Route::post(
            'domains/{tenantDomain}/verification-challenge',
            [TenantDomainController::class, 'requestVerification'],
        )->middleware($domainVerificationThrottle)->whereNumber('tenantDomain')->name('domains.challenge');
        Route::post('domains/{tenantDomain}/verify', [TenantDomainController::class, 'verify'])
            ->middleware($domainVerificationThrottle)
            ->whereNumber('tenantDomain')
            ->name('domains.verify');
        Route::patch(
            'domains/{tenantDomain}/primary',
            [TenantDomainController::class, 'setPrimary'],
        )->whereNumber('tenantDomain')->name('domains.primary');
        Route::patch(
            'domains/{tenantDomain}/disable',
            [TenantDomainController::class, 'disable'],
        )->whereNumber('tenantDomain')->name('domains.disable');
        Route::delete('domains/{tenantDomain}', [TenantDomainController::class, 'destroy'])
            ->whereNumber('tenantDomain')
            ->name('domains.destroy');

        Route::get('documents', [TenantDocumentController::class, 'index'])
            ->name('documents.index');
        Route::post('documents', [TenantDocumentController::class, 'store'])
            ->name('documents.store');
        Route::get('documents/{tenantDocument}', [TenantDocumentController::class, 'show'])
            ->whereNumber('tenantDocument')
            ->name('documents.show');
        Route::patch('documents/{tenantDocument}', [TenantDocumentController::class, 'update'])
            ->whereNumber('tenantDocument')
            ->name('documents.update');
        Route::delete('documents/{tenantDocument}', [TenantDocumentController::class, 'destroy'])
            ->whereNumber('tenantDocument')
            ->name('documents.destroy');
        Route::get(
            'documents/{tenantDocument}/download',
            [TenantDocumentController::class, 'download'],
        )->whereNumber('tenantDocument')->name('documents.download');
    });
