<?php

namespace Modules\Core\Services;

class FeatureFlagService
{
    protected TenantContext $tenantContext;

    protected CacheService $cacheService;

    public function __construct(TenantContext $tenantContext, CacheService $cacheService)
    {
        $this->tenantContext = $tenantContext;
        $this->cacheService = $cacheService;
    }

    public function isEnabled(string $feature): bool
    {
        return $this->cacheService->remember(
            "feature:{$feature}",
            3600,
            function () use ($feature) {
                $tenant = $this->tenantContext->getTenant();

                if (! $tenant) {
                    return $this->getDefaultValue($feature);
                }

                $features = $tenant->getSetting('features', []);

                if (array_key_exists($feature, $features)) {
                    return (bool) $features[$feature];
                }

                return $this->getDefaultValue($feature);
            }
        );
    }

    public function enable(string $feature): void
    {
        $this->setFeature($feature, true);
    }

    public function disable(string $feature): void
    {
        $this->setFeature($feature, false);
    }

    protected function setFeature(string $feature, bool $enabled): void
    {
        $tenant = $this->tenantContext->getTenant();

        if (! $tenant) {
            throw new \RuntimeException('Cannot set feature flag without tenant context');
        }

        $features = $tenant->getSetting('features', []);
        $features[$feature] = $enabled;

        $tenant->setSetting('features', $features);
        $tenant->save();

        $this->cacheService->forget("feature:{$feature}");
    }

    public function getAll(): array
    {
        $tenant = $this->tenantContext->getTenant();

        if (! $tenant) {
            return [];
        }

        return $tenant->getSetting('features', []);
    }

    protected function getDefaultValue(string $feature): bool
    {
        $defaults = config('features.defaults', []);

        return $defaults[$feature] ?? false;
    }
}
