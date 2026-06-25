<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantReadinessCheck;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantBaseCurrencyReadinessInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPolicy;

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
        private readonly TenantBaseCurrencyReadinessInterface $baseCurrencies,
        private readonly TenantSubscriptionPolicy $subscriptionPolicy,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /**
     * @return array{
     *   ready:bool,
     *   tenant_id:int,
     *   onboarding_status:string,
     *   checks:array<string,bool>,
     *   blockers:list<array{code:string,stage:string,message:string}>
     * }
     */
    public function inspect(int $tenantId, bool $lockForUpdate = false): array
    {
        $tenant = $lockForUpdate
            ? $this->tenants->lockById($tenantId)
            : $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return [
                'ready' => false,
                'tenant_id' => $tenantId,
                'onboarding_status' => 'missing',
                'checks' => [],
                'blockers' => [[
                    'code' => 'TENANT_NOT_FOUND',
                    'stage' => 'identity',
                    'message' => 'Tenant was not found.',
                ]],
            ];
        }

        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenant,
            $tenantId,
            $lockForUpdate,
        ): array {
            $stateQuery = $this->states->newQuery()->where('tenant_id', $tenantId);
            if ($lockForUpdate) {
                $stateQuery->lockForUpdate();
            }
            $state = $stateQuery->first();

            $subscription = $this->subscriptions->findCurrentByTenant($tenantId, $lockForUpdate);
            $subscriptionPayload = $subscription?->toArray();
            $revision = is_array($subscriptionPayload['revision'] ?? null)
                ? $subscriptionPayload['revision']
                : null;
            $plan = is_array($revision['plan'] ?? null) ? $revision['plan'] : null;
            $primaryDomain = $this->domains->findPrimaryByTenant($tenantId, $lockForUpdate);

            $rootOrganizationUnitId = $this->positiveInt($state?->getAttribute('root_organization_unit_id'));
            $superAdminRoleId = $this->positiveInt($state?->getAttribute('super_admin_role_id'));
            $invitationId = $this->positiveInt($state?->getAttribute('invitation_id'));
            $acceptedAdministratorId = $this->authentication->acceptedInitialAdministratorUserId(
                $tenantId,
                $invitationId,
                $lockForUpdate,
            );

            $rootReady = $rootOrganizationUnitId !== null
                && $this->organizations->isReady($tenantId, $lockForUpdate);
            $catalogueReady = $this->access->catalogueIsReady($tenantId, $lockForUpdate);
            $superAdminReady = $superAdminRoleId !== null
                && $this->access->superAdminRoleIsReady($tenantId, $lockForUpdate);
            $providerReady = $this->authentication->providerIsReady($tenantId, $lockForUpdate);
            $invitationAccepted = $acceptedAdministratorId !== null;
            $operationalAdministrator = $acceptedAdministratorId !== null
                && $rootOrganizationUnitId !== null
                && $superAdminRoleId !== null
                && $this->access->hasOperationalAdministrator(
                    $tenantId,
                    $acceptedAdministratorId,
                    $rootOrganizationUnitId,
                    $superAdminRoleId,
                    $lockForUpdate,
                );

            $checks = [
                TenantReadinessCheck::ROOT_ORGANIZATION => $rootReady,
                TenantReadinessCheck::PERMISSION_CATALOGUE => $catalogueReady,
                TenantReadinessCheck::SUPER_ADMIN_ACCESS => $superAdminReady,
                TenantReadinessCheck::AUTHENTICATION_PROVIDER => $providerReady,
                TenantReadinessCheck::ADMINISTRATOR_INVITATION_ACCEPTED => $invitationAccepted,
                TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR => $operationalAdministrator,
                TenantReadinessCheck::BASE_CURRENCY => $this->baseCurrencies->isActive(
                    $this->positiveInt($tenant->get('base_currency_id')),
                    $lockForUpdate,
                ),
                TenantReadinessCheck::ACTIVE_PLAN => $plan !== null && (bool) ($plan['is_active'] ?? false),
                TenantReadinessCheck::SUBSCRIPTION_VALID => $this->subscriptionPolicy->isUsable($subscriptionPayload),
                TenantReadinessCheck::PRIMARY_DOMAIN_READY => $primaryDomain !== null
                    && $primaryDomain->get('operational_status') === TenantDomainOperationalStatus::READY,
            ];

            $blockers = $this->blockers($checks);

            return [
                'ready' => $blockers === [],
                'tenant_id' => $tenantId,
                'onboarding_status' => $this->resolveStatus($state, $checks, $blockers === []),
                'checks' => $checks,
                'blockers' => $blockers,
            ];
        });
    }

    /**
     * @param array<string, bool> $checks
     * @return list<array{code:string,stage:string,message:string}>
     */
    private function blockers(array $checks): array
    {
        $messages = TenantReadinessCheck::messages();
        $blockers = [];

        foreach ($checks as $code => $passed) {
            if ($passed) {
                continue;
            }

            $blockers[] = [
                'code' => strtoupper($code),
                'stage' => $this->stage($code),
                'message' => $messages[$code] ?? 'Complete the required tenant readiness step.',
            ];
        }

        return $blockers;
    }

    /** @param array<string, bool> $checks */
    private function resolveStatus(?TenantOnboardingStateModel $state, array $checks, bool $ready): string
    {
        if ($ready) {
            return $state?->getAttribute('status') === TenantOnboardingStatus::COMPLETED
                ? TenantOnboardingStatus::COMPLETED
                : TenantOnboardingStatus::READY;
        }

        if (
            ($checks[TenantReadinessCheck::ROOT_ORGANIZATION] ?? false)
            && ($checks[TenantReadinessCheck::PERMISSION_CATALOGUE] ?? false)
            && ($checks[TenantReadinessCheck::SUPER_ADMIN_ACCESS] ?? false)
            && ($checks[TenantReadinessCheck::AUTHENTICATION_PROVIDER] ?? false)
            && ! ($checks[TenantReadinessCheck::ADMINISTRATOR_INVITATION_ACCEPTED] ?? false)
        ) {
            return TenantOnboardingStatus::AWAITING_ADMINISTRATOR;
        }

        if (
            ($checks[TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR] ?? false)
            && ! ($checks[TenantReadinessCheck::PRIMARY_DOMAIN_READY] ?? false)
        ) {
            return TenantOnboardingStatus::AWAITING_DOMAIN;
        }

        $stored = (string) ($state?->getAttribute('status') ?? TenantOnboardingStatus::PENDING);

        return in_array($stored, [TenantOnboardingStatus::FAILED, TenantOnboardingStatus::PROVISIONING], true)
            ? $stored
            : TenantOnboardingStatus::PENDING;
    }

    private function stage(string $check): string
    {
        return match ($check) {
            TenantReadinessCheck::ROOT_ORGANIZATION,
            TenantReadinessCheck::PERMISSION_CATALOGUE,
            TenantReadinessCheck::SUPER_ADMIN_ACCESS,
            TenantReadinessCheck::AUTHENTICATION_PROVIDER,
            TenantReadinessCheck::ADMINISTRATOR_INVITATION_ACCEPTED,
            TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR => 'foundation',
            TenantReadinessCheck::BASE_CURRENCY => 'identity',
            TenantReadinessCheck::ACTIVE_PLAN,
            TenantReadinessCheck::SUBSCRIPTION_VALID => 'subscription',
            TenantReadinessCheck::PRIMARY_DOMAIN_READY => 'domain',
            default => 'readiness',
        };
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
