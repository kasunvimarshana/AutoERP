<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\TenantConfig;
use Illuminate\Support\Facades\Cache;

final class TenantConfigService
{
    private const CACHE_PREFIX = 'tenant_config:';

    /**
     * Get a tenant-specific configuration value, falling back to SSO global defaults.
     */
    public function get(string $tenantId, string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $tenantId . ':' . $key;
        $cacheTtl = (int) config('sso.feature_flags.cache_ttl_seconds', 300);

        return Cache::remember($cacheKey, now()->addSeconds($cacheTtl), function () use ($tenantId, $key, $default): mixed {
            /** @var TenantConfig|null $config */
            $config = TenantConfig::where('tenant_id', $tenantId)
                ->where('key', $key)
                ->first();

            if ($config === null) {
                return $default;
            }

            return $config->typedValue();
        });
    }

    /**
     * Set or update a tenant-specific configuration value.
     *
     * @param array<string, mixed> $options
     */
    public function set(
        string $tenantId,
        string $key,
        mixed $value,
        array $options = []
    ): TenantConfig {
        $serialized = is_array($value) ? json_encode($value) : (string) $value;

        /** @var TenantConfig $config */
        $config = TenantConfig::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            array_merge([
                'value'        => $serialized,
                'type'         => $options['type'] ?? 'string',
                'group'        => $options['group'] ?? 'general',
                'is_sensitive' => $options['is_sensitive'] ?? false,
                'description'  => $options['description'] ?? null,
            ])
        );

        $this->invalidateCache($tenantId, $key);

        return $config;
    }

    /**
     * Delete a tenant-specific configuration value.
     */
    public function delete(string $tenantId, string $key): bool
    {
        $deleted = (bool) TenantConfig::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->delete();

        $this->invalidateCache($tenantId, $key);

        return $deleted;
    }

    /**
     * Get all configuration values for a tenant grouped by group.
     *
     * @return array<string, mixed>
     */
    public function getAllForTenant(string $tenantId): array
    {
        $configs = TenantConfig::where('tenant_id', $tenantId)
            ->get();

        $result = [];
        foreach ($configs as $config) {
            /** @var TenantConfig $config */
            if (!$config->is_sensitive) {
                $result[$config->group][$config->key] = $config->typedValue();
            }
        }

        return $result;
    }

    /**
     * Get the effective access token TTL for a tenant (in minutes).
     */
    public function getAccessTokenTtl(string $tenantId): int
    {
        /** @var int $default */
        $default = config('sso.token.access_ttl_minutes', 15);
        return (int) $this->get($tenantId, 'token.access_ttl_minutes', $default);
    }

    /**
     * Get the effective refresh token TTL for a tenant (in days).
     */
    public function getRefreshTokenTtl(string $tenantId): int
    {
        /** @var int $default */
        $default = config('sso.token.refresh_ttl_days', 30);
        return (int) $this->get($tenantId, 'token.refresh_ttl_days', $default);
    }

    private function invalidateCache(string $tenantId, string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $tenantId . ':' . $key);
    }
}
