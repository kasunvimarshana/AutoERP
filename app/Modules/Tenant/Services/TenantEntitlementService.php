<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use DateTimeImmutable;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Plans\TenantPlanSchema;

final class TenantEntitlementService
{
    public function __construct(
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantPlanSchema $schema,
        private readonly TenantExecutionContextInterface $executionContext,
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

        $subscription = $this->executionContext->runForTenant(
            $tenantId,
            fn () => $this->subscriptions->findCurrentByTenant($tenantId),
        );
        if ($subscription === null || ! $this->isUsable($subscription->toArray())) {
            return ['modules' => [], 'limits' => []];
        }

        $revision = $subscription->get('revision');
        if (! is_array($revision)) {
            return ['modules' => [], 'limits' => []];
        }

        $features = $this->schema->normalizeFeatures($revision['features'] ?? null);

        return [
            'modules' => $features['enabled_modules'],
            'limits' => $this->schema->normalizeLimits($revision['limits'] ?? null),
        ];
    }

    /** @param array<string, mixed> $subscription */
    private function isUsable(array $subscription): bool
    {
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
