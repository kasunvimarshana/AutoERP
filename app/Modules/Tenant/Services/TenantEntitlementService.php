<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Contracts\TenantEntitlementReaderInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Plans\TenantModuleCatalogue;
use Modules\Tenant\Services\Plans\TenantPlanSchema;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPolicy;

final class TenantEntitlementService implements TenantEntitlementReaderInterface
{
    public function __construct(
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantPlanSchema $schema,
        private readonly TenantModuleCatalogue $modules,
        private readonly TenantSubscriptionPolicy $policy,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return list<string> */
    public function enabledModules(int $tenantId): array
    {
        return $this->entitlements($tenantId)['modules'];
    }

    public function featureEnabled(int $tenantId, string $feature): bool
    {
        $feature = $this->modules->assertKnown($feature);

        return in_array($feature, $this->enabledModules($tenantId), true);
    }

    public function limit(int $tenantId, string $limit): ?int
    {
        return $this->entitlements($tenantId)['limits'][$limit] ?? null;
    }

    /** @return array{modules:list<string>,limits:array<string,int>} */
    private function entitlements(int $tenantId): array
    {
        if ($tenantId < 1) {
            return ['modules' => [], 'limits' => []];
        }

        $foundationModules = $this->modules->foundationCodes();

        $subscription = $this->executionContext->runForTenant(
            $tenantId,
            fn () => $this->subscriptions->findCurrentByTenant($tenantId),
        );
        if ($subscription === null || ! $this->policy->isUsable($subscription->toArray())) {
            return ['modules' => $foundationModules, 'limits' => []];
        }

        return [
            'modules' => array_values(array_unique([
                ...$foundationModules,
                ...$this->schema->normalizeFeatures($subscription->get('plan_features'))['enabled_modules'],
            ])),
            'limits' => $this->schema->normalizeLimits($subscription->get('plan_limits')),
        ];
    }
}
