<?php

namespace App\Services;

use App\Domain\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RuntimeConfigService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'runtime_config_';

    /**
     * Load and apply tenant-specific runtime configuration.
     * Merges tenant config into the application config without restart.
     */
    public function loadForTenant(Tenant $tenant): void
    {
        $config = $this->getTenantConfig($tenant);
        $this->applyConfig($config, $tenant->id);
    }

    /**
     * Get tenant configuration, using cache.
     */
    public function getTenantConfig(Tenant $tenant): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . $tenant->id,
            self::CACHE_TTL,
            fn () => $this->buildTenantConfig($tenant)
        );
    }

    /**
     * Build complete tenant configuration by merging DB config with defaults.
     */
    private function buildTenantConfig(Tenant $tenant): array
    {
        $dbConfig  = $tenant->config ?? [];
        $settings  = $tenant->settings ?? [];
        $features  = $tenant->features ?? [];

        return [
            'database'    => $this->buildDatabaseConfig($tenant, $dbConfig),
            'mail'        => $this->buildMailConfig($settings),
            'cache'       => $this->buildCacheConfig($settings),
            'queue'       => $this->buildQueueConfig($settings),
            'features'    => array_merge(
                config('tenant.default_features', []),
                $features
            ),
            'limits'      => config("tenant.plans.{$tenant->plan}", []),
            'tenant_meta' => [
                'id'        => $tenant->id,
                'name'      => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'plan'      => $tenant->plan,
                'timezone'  => $settings['timezone'] ?? 'UTC',
                'locale'    => $settings['locale'] ?? 'en',
            ],
        ];
    }

    /**
     * Build database configuration for this tenant.
     */
    private function buildDatabaseConfig(Tenant $tenant, array $dbConfig): array
    {
        $strategy    = config('tenant.db_strategy', 'schema');
        $baseConfig  = config('database.connections.pgsql');

        if ($strategy === 'schema') {
            return array_merge($baseConfig, [
                'search_path' => $tenant->getSchemaName(),
            ]);
        }

        if ($strategy === 'database') {
            return array_merge($baseConfig, [
                'database' => config('tenant.db_prefix') . $tenant->subdomain,
                'host'     => $dbConfig['host'] ?? $baseConfig['host'],
                'port'     => $dbConfig['port'] ?? $baseConfig['port'],
                'username' => $dbConfig['username'] ?? $baseConfig['username'],
                'password' => $dbConfig['password'] ?? $baseConfig['password'],
            ]);
        }

        return $baseConfig;
    }

    /**
     * Build mail configuration from tenant settings.
     */
    private function buildMailConfig(array $settings): array
    {
        $mail = $settings['mail'] ?? [];

        if (empty($mail)) {
            return config('mail');
        }

        return array_merge(config('mail', []), [
            'default'  => $mail['driver'] ?? config('mail.default'),
            'from'     => [
                'address' => $mail['from_address'] ?? config('mail.from.address'),
                'name'    => $mail['from_name'] ?? config('mail.from.name'),
            ],
        ]);
    }

    /**
     * Build cache configuration from tenant settings.
     */
    private function buildCacheConfig(array $settings): array
    {
        $cache = $settings['cache'] ?? [];

        return array_merge(config('cache', []), [
            'default' => $cache['driver'] ?? config('cache.default'),
            'prefix'  => config('cache.prefix') . ($cache['prefix'] ?? ''),
        ]);
    }

    /**
     * Build queue configuration from tenant settings.
     */
    private function buildQueueConfig(array $settings): array
    {
        $queue = $settings['queue'] ?? [];

        return array_merge(config('queue', []), [
            'default' => $queue['connection'] ?? config('queue.default'),
        ]);
    }

    /**
     * Apply configuration to the running application.
     */
    private function applyConfig(array $config, string $tenantId): void
    {
        // Apply database connection
        if (isset($config['database'])) {
            Config::set(
                "database.connections.tenant_{$tenantId}",
                $config['database']
            );
        }

        // Apply feature flags
        if (isset($config['features'])) {
            foreach ($config['features'] as $key => $value) {
                Config::set("app.features.{$key}", $value);
            }
        }

        // Apply tenant meta
        if (isset($config['tenant_meta'])) {
            Config::set('app.timezone', $config['tenant_meta']['timezone'] ?? 'UTC');
            Config::set('app.locale', $config['tenant_meta']['locale'] ?? 'en');
        }

        Log::debug("Runtime config applied for tenant: {$tenantId}");
    }

    /**
     * Invalidate tenant config cache (e.g., after tenant settings update).
     */
    public function invalidateForTenant(string $tenantId): void
    {
        Cache::forget(self::CACHE_PREFIX . $tenantId);
        Log::info("Runtime config cache invalidated for tenant: {$tenantId}");
    }

    /**
     * Warm up config cache for all active tenants.
     */
    public function warmUpAll(): void
    {
        $tenants = Tenant::active()->get();

        foreach ($tenants as $tenant) {
            try {
                $this->getTenantConfig($tenant);
            } catch (\Throwable $e) {
                Log::error("Failed to warm config for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        Log::info("Runtime config warmed up for " . $tenants->count() . " tenants.");
    }
}
