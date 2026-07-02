<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantReadinessCheck;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;
use Modules\Tenant\Services\Domains\TenantRoutingReadinessPolicy;
use Modules\Tenant\Services\Platform\TenantInfrastructureCapabilityService;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPolicy;
use Psr\Log\LoggerInterface;
use Throwable;

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
        private readonly CurrencyDirectoryInterface $baseCurrencies,
        private readonly TenantSubscriptionPolicy $subscriptionPolicy,
        private readonly TenantRoutingReadinessPolicy $routingReadiness,
        private readonly TenantInfrastructureCapabilityService $infrastructureCapabilities,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array{
     *   ready:bool,
     *   tenant_id:int,
     *   onboarding_status:string,
     *   checks:array<string,bool>,
     *   blockers:list<array{code:string,stage:string,owner:string,action:string,message:string,context?:array<string,mixed>}>,
     *   routing:array{
     *       ready:bool,
     *       mode:string,
     *       message:string,
     *       local_fallback?:array{
     *           supported:bool,
     *           enabled:bool,
     *           configured_tenant_code:?string,
     *           matches_tenant:bool
     *       }
     *   },
     *   infrastructure:array<string,mixed>
     * }
     */
    public function inspect(int $tenantId, bool $lockForUpdate = false): array
    {
        $tenant = $lockForUpdate
            ? $this->tenants->lockById($tenantId)
            : $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return $this->missingTenant($tenantId);
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

            $subscriptionPayload = null;
            $subscriptionFailure = null;
            try {
                $subscription = $this->subscriptions->findCurrentByTenant($tenantId, $lockForUpdate);
                $subscriptionPayload = $subscription?->toArray();
            } catch (Throwable $exception) {
                $subscriptionFailure = [
                    'code' => 'SUBSCRIPTION_DATA_INVALID',
                    'stage' => 'subscription',
                    'owner' => 'Tenant subscription',
                    'action' => 'Review the deployment schema and repair the persisted subscription revision.',
                    'message' => 'Stored subscription data is invalid or unavailable. Review the deployment schema and persisted revision data.',
                ];
                $this->logger->error('Tenant readiness could not read subscription data.', [
                    'tenant_id' => $tenantId,
                    'exception' => $exception,
                ]);
            }

            $revision = is_array($subscriptionPayload['revision'] ?? null)
                ? $subscriptionPayload['revision']
                : null;
            $plan = is_array($revision['plan'] ?? null) ? $revision['plan'] : null;
            $primaryDomain = $this->domains->findPrimaryByTenant($tenantId, $lockForUpdate);

            $rootOrganizationUnitId = $this->positiveInt($state?->getAttribute('root_organization_unit_id'));
            $superAdminRoleId = $this->positiveInt($state?->getAttribute('super_admin_role_id'));
            $administratorUserId = $this->positiveInt($state?->getAttribute('administrator_user_id'));
            $administratorEmail = strtolower(trim((string) $state?->getAttribute('initial_admin_email')));

            $rootReady = $rootOrganizationUnitId !== null
                && $this->organizations->isReady($tenantId, $rootOrganizationUnitId, $lockForUpdate);
            $catalogueReady = $this->access->catalogueIsReady($tenantId, $lockForUpdate);
            $superAdminReady = $superAdminRoleId !== null
                && $this->access->superAdminRoleIsReady($tenantId, $superAdminRoleId, $lockForUpdate);
            $providerReady = $this->authentication->providerIsReady($tenantId, $lockForUpdate);
            $administratorAccountReady = $administratorUserId !== null;
            $operationalAdministrator = $administratorUserId !== null
                && $rootOrganizationUnitId !== null
                && $superAdminRoleId !== null
                && $this->access->hasOperationalAdministrator(
                    $tenantId,
                    $administratorUserId,
                    $rootOrganizationUnitId,
                    $superAdminRoleId,
                    $lockForUpdate,
                    $administratorEmail,
                );
            $routing = $this->routingReadiness->inspect(
                (string) $tenant->get('code'),
                $primaryDomain,
            );

            $subscriptionValid = false;
            if ($subscriptionFailure === null) {
                try {
                    $subscriptionValid = $this->subscriptionPolicy->isUsable($subscriptionPayload);
                } catch (Throwable $exception) {
                    $subscriptionFailure = [
                        'code' => 'SUBSCRIPTION_DATA_INVALID',
                        'stage' => 'subscription',
                        'owner' => 'Tenant subscription',
                        'action' => 'Correct the stored subscription dates and current revision pointer.',
                        'message' => 'Stored subscription dates or state are invalid. Correct the persisted subscription revision before activation.',
                    ];
                    $this->logger->error('Tenant readiness rejected malformed subscription data.', [
                        'tenant_id' => $tenantId,
                        'exception' => $exception,
                    ]);
                }
            }

            $checks = [
                TenantReadinessCheck::ROOT_ORGANIZATION => $rootReady,
                TenantReadinessCheck::PERMISSION_CATALOGUE => $catalogueReady,
                TenantReadinessCheck::SUPER_ADMIN_ACCESS => $superAdminReady,
                TenantReadinessCheck::AUTHENTICATION_PROVIDER => $providerReady,
                TenantReadinessCheck::ADMINISTRATOR_ACCOUNT_READY => $administratorAccountReady,
                TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR => $operationalAdministrator,
                TenantReadinessCheck::BASE_CURRENCY => $this->baseCurrencies->isActive(
                    $this->positiveInt($tenant->get('base_currency_id')),
                    $lockForUpdate,
                ),
                TenantReadinessCheck::ACTIVE_PLAN => $plan !== null && (bool) ($plan['is_active'] ?? false),
                TenantReadinessCheck::SUBSCRIPTION_VALID => $subscriptionValid,
                TenantReadinessCheck::PRIMARY_DOMAIN_READY => $routing['ready'],
            ];

            $additionalBlockers = $subscriptionFailure === null ? [] : [$subscriptionFailure];
            $blockers = $this->blockers($checks, $additionalBlockers);

            return [
                'ready' => $blockers === [],
                'tenant_id' => $tenantId,
                'onboarding_status' => $this->resolveStatus($state, $checks, $blockers === []),
                'checks' => $checks,
                'blockers' => $blockers,
                'routing' => $routing,
                'infrastructure' => $this->infrastructureCapabilities->inspect(),
            ];
        });
    }

    /**
     * @param array<string, bool> $checks
     * @param list<array{code:string,stage:string,owner:string,action:string,message:string,context?:array<string,mixed>}> $additional
     * @return list<array{code:string,stage:string,owner:string,action:string,message:string,context?:array<string,mixed>}>
     */
    private function blockers(array $checks, array $additional = []): array
    {
        $messages = TenantReadinessCheck::messages();
        $blockers = $additional;
        $additionalCodes = array_column($additional, 'code');

        foreach ($checks as $code => $passed) {
            if ($passed || ($code === TenantReadinessCheck::SUBSCRIPTION_VALID
                && in_array('SUBSCRIPTION_DATA_INVALID', $additionalCodes, true))) {
                continue;
            }

            $blockers[] = [
                'code' => strtoupper($code),
                'stage' => $this->stage($code),
                'owner' => $this->owner($code),
                'action' => $this->action($code),
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
            && ! ($checks[TenantReadinessCheck::ADMINISTRATOR_ACCOUNT_READY] ?? false)
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
            TenantReadinessCheck::ADMINISTRATOR_ACCOUNT_READY,
            TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR => 'foundation',
            TenantReadinessCheck::BASE_CURRENCY => 'identity',
            TenantReadinessCheck::ACTIVE_PLAN,
            TenantReadinessCheck::SUBSCRIPTION_VALID => 'subscription',
            TenantReadinessCheck::PRIMARY_DOMAIN_READY => 'domain',
            default => 'readiness',
        };
    }

    private function owner(string $check): string
    {
        return match ($check) {
            TenantReadinessCheck::ROOT_ORGANIZATION => 'Organization Unit',
            TenantReadinessCheck::PERMISSION_CATALOGUE,
            TenantReadinessCheck::SUPER_ADMIN_ACCESS => 'User access',
            TenantReadinessCheck::AUTHENTICATION_PROVIDER,
            TenantReadinessCheck::ADMINISTRATOR_ACCOUNT_READY,
            TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR => 'User access',
            TenantReadinessCheck::BASE_CURRENCY => 'Tenant identity',
            TenantReadinessCheck::ACTIVE_PLAN,
            TenantReadinessCheck::SUBSCRIPTION_VALID => 'Tenant subscription',
            TenantReadinessCheck::PRIMARY_DOMAIN_READY => 'Tenant domain',
            default => 'Tenant onboarding',
        };
    }

    private function action(string $check): string
    {
        return match ($check) {
            TenantReadinessCheck::ROOT_ORGANIZATION => 'Repair and recheck tenant foundation provisioning.',
            TenantReadinessCheck::PERMISSION_CATALOGUE => 'Synchronize the tenant permission catalogue.',
            TenantReadinessCheck::SUPER_ADMIN_ACCESS => 'Repair the fully granted Super Admin role.',
            TenantReadinessCheck::AUTHENTICATION_PROVIDER => 'Provision an active tenant authentication provider.',
            TenantReadinessCheck::ADMINISTRATOR_ACCOUNT_READY => 'Create the initial administrator account.',
            TenantReadinessCheck::OPERATIONAL_ADMINISTRATOR => 'Ensure the administrator is active and assigned to the protected root and Super Admin role.',
            TenantReadinessCheck::BASE_CURRENCY => 'Select an active base accounting currency.',
            TenantReadinessCheck::ACTIVE_PLAN => 'Select an active plan revision.',
            TenantReadinessCheck::SUBSCRIPTION_VALID => 'Assign or correct a usable current subscription revision.',
            TenantReadinessCheck::PRIMARY_DOMAIN_READY => 'Verify a public primary domain or configure the explicit local/testing fallback.',
            default => 'Review and complete this readiness requirement.',
        };
    }

    /** @return array<string, mixed> */
    private function missingTenant(int $tenantId): array
    {
        return [
            'ready' => false,
            'tenant_id' => $tenantId,
            'onboarding_status' => 'missing',
            'checks' => [],
            'blockers' => [[
                'code' => 'TENANT_NOT_FOUND',
                'stage' => 'identity',
                'owner' => 'Tenant',
                'action' => 'Refresh the tenant list and select an existing tenant.',
                'message' => 'Tenant was not found.',
            ]],
            'routing' => [
                'ready' => false,
                'mode' => TenantRoutingReadinessPolicy::MODE_UNAVAILABLE,
                'message' => 'Tenant routing cannot be evaluated.',
            ],
            'infrastructure' => $this->infrastructureCapabilities->inspect(),
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
