<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant-scoped runtime configuration service.
 *
 * Provides a typed get/set interface over the `business_settings` key-value
 * table.  All reads are cached with a two-tier strategy:
 *   1. Per-request in-memory map  — avoids N+1 queries within one lifecycle.
 *   2. Laravel cache (Redis / DB) — avoids database hits across requests.
 *      TTL is configurable via SETTINGS_CACHE_TTL_SECONDS (default 300 s).
 */
class BusinessSettingService
{
    /** Per-request in-memory cache: tenant → key → value */
    private array $cache = [];

    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = (int) env('SETTINGS_CACHE_TTL_SECONDS', 300);
    }

    /**
     * Retrieve a single setting value for a tenant.
     *
     * @param  string  $tenantId  Tenant identifier
     * @param  string  $key  Setting key
     * @param  string|null  $default  Fallback when key is not found
     */
    public function get(string $tenantId, string $key, ?string $default = null): ?string
    {
        if (! array_key_exists($key, $this->cache[$tenantId] ?? [])) {
            $cacheKey = $this->cacheKey($tenantId, $key);
            $value = Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId, $key) {
                return BusinessSetting::where('tenant_id', $tenantId)
                    ->where('key', $key)
                    ->value('value');
            });

            $this->cache[$tenantId][$key] = $value;
        }

        return $this->cache[$tenantId][$key] ?? $default;
    }

    /**
     * Upsert a setting for a tenant.
     */
    public function set(string $tenantId, string $key, ?string $value, string $group = 'general', bool $isPublic = false): BusinessSetting
    {
        $setting = BusinessSetting::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value, 'group' => $group, 'is_public' => $isPublic]
        );

        $this->invalidate($tenantId, $key);

        return $setting;
    }

    /**
     * Set multiple settings in a single call (bulk upsert).
     *
     * @param  string  $tenantId  Tenant identifier
     * @param  array<string, mixed>  $settings  Associative array of key → value
     * @param  string  $group  Group label applied to all keys
     */
    public function setBulk(string $tenantId, array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            $this->set($tenantId, (string) $key, $value === null ? null : (string) $value, $group);
        }
    }

    /**
     * Retrieve all settings for a tenant, optionally filtered by group.
     *
     * @return Collection<int, BusinessSetting>
     */
    public function all(string $tenantId, ?string $group = null): Collection
    {
        $query = BusinessSetting::where('tenant_id', $tenantId);

        if ($group !== null) {
            $query->where('group', $group);
        }

        return $query->orderBy('group')->orderBy('key')->get();
    }

    /**
     * Delete a setting for a tenant.
     */
    public function delete(string $tenantId, string $key): bool
    {
        $deleted = (bool) BusinessSetting::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->delete();

        $this->invalidate($tenantId, $key);

        return $deleted;
    }

    private function cacheKey(string $tenantId, string $key): string
    {
        return "business_settings:{$tenantId}:{$key}";
    }

    private function invalidate(string $tenantId, string $key): void
    {
        Cache::forget($this->cacheKey($tenantId, $key));
        unset($this->cache[$tenantId][$key]);
    }
}
