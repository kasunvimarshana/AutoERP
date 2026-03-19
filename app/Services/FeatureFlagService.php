<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

final class FeatureFlagService
{
    private const CACHE_PREFIX = 'feature_flag:';

    /**
     * Check whether a feature flag is enabled for a given tenant and optional context.
     *
     * @param array<string, mixed> $context
     */
    public function isEnabled(string $flagName, ?string $tenantId = null, array $context = []): bool
    {
        $cacheKey = self::CACHE_PREFIX . ($tenantId ?? 'global') . ':' . $flagName;
        $cacheTtl = (int) config('sso.feature_flags.cache_ttl_seconds', 300);

        return (bool) Cache::remember($cacheKey, now()->addSeconds($cacheTtl), function () use ($flagName, $tenantId, $context): bool {
            // Check tenant-specific flag first
            if ($tenantId !== null) {
                /** @var FeatureFlag|null $tenantFlag */
                $tenantFlag = FeatureFlag::where('tenant_id', $tenantId)
                    ->where('name', $flagName)
                    ->first();

                if ($tenantFlag !== null) {
                    return $tenantFlag->isEnabledForContext($context);
                }
            }

            // Fall back to global flag (null tenant_id)
            /** @var FeatureFlag|null $globalFlag */
            $globalFlag = FeatureFlag::whereNull('tenant_id')
                ->where('name', $flagName)
                ->first();

            if ($globalFlag !== null) {
                return $globalFlag->isEnabledForContext($context);
            }

            return false;
        });
    }

    /**
     * Create or update a feature flag.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(
        string $flagName,
        bool $isEnabled,
        ?string $tenantId = null,
        array $data = []
    ): FeatureFlag {
        /** @var FeatureFlag $flag */
        $flag = FeatureFlag::updateOrCreate(
            ['name' => $flagName, 'tenant_id' => $tenantId],
            array_merge([
                'is_enabled'         => $isEnabled,
                'rollout_percentage' => $data['rollout_percentage'] ?? 100,
                'conditions'         => $data['conditions'] ?? null,
                'description'        => $data['description'] ?? null,
            ])
        );

        $this->invalidateCache($flagName, $tenantId);

        return $flag;
    }

    /**
     * Delete a feature flag.
     */
    public function delete(string $flagName, ?string $tenantId = null): bool
    {
        $deleted = (bool) FeatureFlag::where('name', $flagName)
            ->where('tenant_id', $tenantId)
            ->delete();

        $this->invalidateCache($flagName, $tenantId);

        return $deleted;
    }

    /**
     * List all flags for a given tenant (including global flags).
     *
     * @return array<int, FeatureFlag>
     */
    public function listForTenant(?string $tenantId): array
    {
        $query = FeatureFlag::query();

        if ($tenantId !== null) {
            $query->where(function ($q) use ($tenantId): void {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->orderBy('name')->get()->all();
    }

    private function invalidateCache(string $flagName, ?string $tenantId): void
    {
        Cache::forget(self::CACHE_PREFIX . ($tenantId ?? 'global') . ':' . $flagName);
    }
}
