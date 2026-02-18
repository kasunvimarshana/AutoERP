<?php

namespace Modules\Core\Services;

class ConfigurationService
{
    protected TenantContext $tenantContext;

    protected CacheService $cacheService;

    public function __construct(TenantContext $tenantContext, CacheService $cacheService)
    {
        $this->tenantContext = $tenantContext;
        $this->cacheService = $cacheService;
    }

    public function get(string $key, $default = null)
    {
        return $this->cacheService->remember(
            "config:{$key}",
            3600,
            function () use ($key, $default) {
                $tenant = $this->tenantContext->getTenant();

                return $tenant?->getSetting($key, $default) ?? $default;
            }
        );
    }

    public function set(string $key, $value): void
    {
        $tenant = $this->tenantContext->getTenant();

        if (! $tenant) {
            throw new \RuntimeException('Cannot set configuration without tenant context');
        }

        $tenant->setSetting($key, $value);
        $tenant->save();

        $this->cacheService->forget("config:{$key}");
    }

    public function has(string $key): bool
    {
        $tenant = $this->tenantContext->getTenant();

        return $tenant && $tenant->getSetting($key) !== null;
    }

    public function forget(string $key): void
    {
        $tenant = $this->tenantContext->getTenant();

        if (! $tenant) {
            throw new \RuntimeException('Cannot forget configuration without tenant context');
        }

        $settings = $tenant->settings ?? [];
        unset($settings[$key]);
        $tenant->settings = $settings;
        $tenant->save();

        $this->cacheService->forget("config:{$key}");
    }

    public function all(): array
    {
        $tenant = $this->tenantContext->getTenant();

        return $tenant?->settings ?? [];
    }
}
