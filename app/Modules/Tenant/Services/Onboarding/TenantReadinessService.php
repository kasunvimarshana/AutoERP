<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use DateTimeImmutable;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class TenantReadinessService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantOnboardingStateModel $states,
        private readonly TenantOrganizationProvisionerInterface $organizations,
        private readonly TenantAccessProvisionerInterface $access,
        private readonly TenantAuthenticationProvisionerInterface $authentication,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /**
     * @return array{
     *   ready:bool,
     *   tenant_id:int,
     *   onboarding_status:string,
     *   checks:array<string,bool>,
     *   blockers:list<array{code:string,message:string}>
     * }
     */
    public function inspect(int $tenantId): array
    {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return [
                'ready' => false,
                'tenant_id' => $tenantId,
                'onboarding_status' => 'missing',
                'checks' => [],
                'blockers' => [['code' => 'TENANT_NOT_FOUND', 'message' => 'Tenant was not found.']],
            ];
        }

        return $this->executionContext->runForTenant($tenantId, function () use ($tenant, $tenantId): array {
            $state = $this->states->newQuery()->where('tenant_id', $tenantId)->first();
            $subscription = $this->subscriptions->findCurrentByTenant($tenantId);
            $revision = is_array($subscription?->get('revision')) ? $subscription?->get('revision') : null;
            $plan = is_array($revision['plan'] ?? null) ? $revision['plan'] : null;
            $primaryDomain = $this->domains->findPrimaryByTenant($tenantId);

            $checks = [
                'organization_structure' => $this->organizations->isReady($tenantId),
                'access_catalogue' => $this->access->isReady($tenantId),
                'authentication' => $this->authentication->isReady($tenantId),
                'base_currency' => is_numeric($tenant->get('base_currency_id')),
                'active_plan' => $plan !== null && (bool) ($plan['is_active'] ?? false),
                'subscription_valid' => $this->subscriptionIsValid($subscription?->toArray()),
                'verified_primary_domain' => $primaryDomain !== null,
            ];

            $messages = [
                'organization_structure' => 'Create the tenant root organization unit.',
                'access_catalogue' => 'Provision the permission catalogue and Super Admin role.',
                'authentication' => 'Provision an authentication provider and initial administrator invitation.',
                'base_currency' => 'Select an active base accounting currency.',
                'active_plan' => 'Assign a revision from an active subscription plan.',
                'subscription_valid' => 'Assign an unexpired current subscription.',
                'verified_primary_domain' => 'Verify and select a primary tenant domain.',
            ];

            $blockers = [];
            foreach ($checks as $code => $passed) {
                if (! $passed) {
                    $blockers[] = ['code' => strtoupper($code), 'message' => $messages[$code]];
                }
            }

            $status = (string) ($state?->getAttribute('status') ?? TenantOnboardingStatus::PENDING);
            if ($blockers === [] && $status !== TenantOnboardingStatus::COMPLETED) {
                $status = TenantOnboardingStatus::READY;
            }

            return [
                'ready' => $blockers === [],
                'tenant_id' => $tenantId,
                'onboarding_status' => $status,
                'checks' => $checks,
                'blockers' => $blockers,
            ];
        });
    }

    /** @param array<string, mixed>|null $subscription */
    private function subscriptionIsValid(?array $subscription): bool
    {
        if ($subscription === null) {
            return false;
        }

        $now = new DateTimeImmutable('now');
        $startsAt = $this->dateTime($subscription['starts_at'] ?? null);
        if ($startsAt === null || $startsAt > $now) {
            return false;
        }

        $status = strtolower(trim((string) ($subscription['status'] ?? '')));
        if ($status === 'trial') {
            $trialEndsAt = $this->dateTime($subscription['trial_ends_at'] ?? null);

            return $trialEndsAt !== null && $trialEndsAt > $now;
        }
        if ($status !== 'active') {
            return false;
        }

        $endsAt = $this->dateTime($subscription['ends_at'] ?? null);

        return $endsAt === null || $endsAt > $now;
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        return $value === null || trim((string) $value) === ''
            ? null
            : new DateTimeImmutable((string) $value);
    }
}
