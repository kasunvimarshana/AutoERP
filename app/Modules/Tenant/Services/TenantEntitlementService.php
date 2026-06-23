<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Plans\TenantPlanSchema;

final class TenantEntitlementService
{
    /** @var array<int, array{modules:list<string>,limits:array<string,int>}> */
    private array $cache = [];

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantPlanSchema $schema,
    ) {}

    /** @return list<string> */
    public function enabledModules(int $tenantId): array
    {
        return $this->entitlements($tenantId)['modules'];
    }

    public function featureEnabled(int $tenantId, string $feature): bool
    {
        return in_array(strtolower(trim($feature)), $this->enabledModules($tenantId), true);
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
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $tenant = $this->tenants->findById($tenantId);
        $planId = $tenant?->get('tenant_plan_id');
        if (! is_numeric($planId) || (int) $planId < 1) {
            return $this->cache[$tenantId] = ['modules' => [], 'limits' => []];
        }

        $plan = $this->plans->findById((int) $planId);
        if ($plan === null || ! (bool) $plan->get('is_active', false)) {
            return $this->cache[$tenantId] = ['modules' => [], 'limits' => []];
        }

        $features = $this->schema->normalizeFeatures($plan->get('features'));
        $limits = $this->schema->normalizeLimits($plan->get('limits'));

        return $this->cache[$tenantId] = [
            'modules' => $features['enabled_modules'],
            'limits' => $limits,
        ];
    }
}
