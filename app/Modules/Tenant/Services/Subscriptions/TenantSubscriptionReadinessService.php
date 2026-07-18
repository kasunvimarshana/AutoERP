<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantPlanRevisionRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Core\Contracts\TenantLimitUsageContributorInterface;
use Modules\Tenant\Services\Plans\TenantPlanSchema;

final class TenantSubscriptionReadinessService
{
    /** @param iterable<TenantLimitUsageContributorInterface> $usageContributors */
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantPlanRevisionRepositoryInterface $revisions,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantPlanSchema $schema,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly iterable $usageContributors,
    ) {}

    /**
     * @return array{
     *   ready:bool,
     *   tenant_id:int,
     *   plan_revision_id:int,
     *   usage:array<string,int>,
     *   limits:array<string,int>,
     *   removed_modules:list<string>,
     *   blockers:list<array{code:string,message:string,context:array<string,mixed>}>
     * }
     */
    public function inspect(int $tenantId, int $planRevisionId, bool $lockForUpdate = false): array
    {
        $tenant = $lockForUpdate
            ? $this->tenants->lockById($tenantId)
            : $this->tenants->findById($tenantId);
        $revision = $this->revisions->findById($planRevisionId, $lockForUpdate);
        $blockers = [];

        if ($tenant === null) {
            $blockers[] = $this->blocker('TENANT_NOT_FOUND', 'Tenant was not found.');
        }
        if ($revision === null || ! (bool) ($revision?->get('plan')['is_active'] ?? false)) {
            $blockers[] = $this->blocker(
                'PLAN_REVISION_UNAVAILABLE',
                'Select a revision that belongs to an active tenant plan.',
            );
        }

        $limits = $revision === null ? [] : $this->schema->normalizeLimits($revision->get('limits'));
        $newModules = $revision === null
            ? []
            : $this->schema->normalizePersistedFeatures($revision->get('features'))['enabled_modules'];
        $usage = [];

        if ($tenant !== null) {
            $usage = $this->executionContext->runForTenant($tenantId, function () use ($tenantId): array {
                $resolved = [];
                foreach ($this->usageContributors as $contributor) {
                    foreach ($contributor->usage($tenantId) as $key => $value) {
                        $resolved[$key] = ($resolved[$key] ?? 0) + max(0, $value);
                    }
                }

                ksort($resolved);

                return $resolved;
            });
        }

        foreach ($limits as $key => $limit) {
            $actual = $usage[$key] ?? 0;
            if ($actual > $limit) {
                $blockers[] = $this->blocker(
                    'LIMIT_BELOW_USAGE',
                    sprintf('%s is already %d, which exceeds the selected limit of %d.', $key, $actual, $limit),
                    ['limit_key' => $key, 'usage' => $actual, 'limit' => $limit],
                );
            }
        }

        $current = $tenant === null
            ? null
            : $this->executionContext->runForTenant(
                $tenantId,
                fn () => $this->subscriptions->findCurrentByTenant($tenantId, $lockForUpdate),
            );
        $currentModules = $current === null
            ? []
            : $this->schema->normalizePersistedFeatures($current->get('plan_features'))['enabled_modules'];
        $removedModules = array_values(array_diff($currentModules, $newModules));
        sort($removedModules);

        if (
            $tenant !== null
            && $tenant->get('status') === TenantStatus::ACTIVE
            && $removedModules !== []
        ) {
            $blockers[] = $this->blocker(
                'ACTIVE_TENANT_MODULE_REMOVAL',
                'Suspend the tenant and complete module-specific closeout before removing enabled modules.',
                ['removed_modules' => $removedModules],
            );
        }

        return [
            'ready' => $blockers === [],
            'tenant_id' => $tenantId,
            'plan_revision_id' => $planRevisionId,
            'usage' => $usage,
            'limits' => $limits,
            'removed_modules' => $removedModules,
            'blockers' => $blockers,
        ];
    }

    /** @param array<string, mixed> $context @return array{code:string,message:string,context:array<string,mixed>} */
    private function blocker(string $code, string $message, array $context = []): array
    {
        return ['code' => $code, 'message' => $message, 'context' => $context];
    }
}
