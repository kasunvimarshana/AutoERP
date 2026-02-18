<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * ConfigurationManager - Manages dynamic runtime configuration
 * 
 * Supports tenant-specific overrides and feature flags
 */
class ConfigurationManager
{
    private array $config = [];
    private array $tenantOverrides = [];

    /**
     * Load configuration from array
     */
    public function load(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get configuration value
     */
    public function get(string $key, $default = null)
    {
        // Check tenant overrides first
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId && isset($this->tenantOverrides[$tenantId])) {
            $value = Arr::get($this->tenantOverrides[$tenantId], $key);
            if ($value !== null) {
                return $value;
            }
        }

        return Arr::get($this->config, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        Arr::set($this->config, $key, $value);
    }

    /**
     * Check if configuration exists
     */
    public function has(string $key): bool
    {
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId && isset($this->tenantOverrides[$tenantId])) {
            if (Arr::has($this->tenantOverrides[$tenantId], $key)) {
                return true;
            }
        }

        return Arr::has($this->config, $key);
    }

    /**
     * Set tenant-specific override
     */
    public function setTenantOverride(string $tenantId, string $key, $value): void
    {
        if (!isset($this->tenantOverrides[$tenantId])) {
            $this->tenantOverrides[$tenantId] = [];
        }

        Arr::set($this->tenantOverrides[$tenantId], $key, $value);
    }

    /**
     * Get all tenant overrides
     */
    public function getTenantOverrides(string $tenantId): array
    {
        return $this->tenantOverrides[$tenantId] ?? [];
    }

    /**
     * Clear tenant overrides
     */
    public function clearTenantOverrides(string $tenantId): void
    {
        unset($this->tenantOverrides[$tenantId]);
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Check if feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) $this->get("features.{$feature}", false);
    }

    /**
     * Get current tenant ID from context
     */
    private function getCurrentTenantId(): ?string
    {
        return app()->bound('current_tenant') ? app('current_tenant')?->id : null;
    }
}
