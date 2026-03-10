<?php

declare(strict_types=1);

namespace App\Infrastructure\MultiTenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * TenantManager
 *
 * Resolves tenant context from the current HTTP request and dynamically
 * reconfigures Laravel's database, cache, queue, and mail settings at runtime
 * without requiring a service restart or redeployment.
 *
 * Tenant configurations are stored in Redis with a configurable TTL and can
 * be refreshed via the management API.
 */
class TenantManager
{
    private const CACHE_KEY_PREFIX = 'tenant:config:';
    private const CACHE_TTL_SECONDS = 3600; // 1 hour — overridable per tenant

    /** @var array<string, mixed>|null */
    private ?array $currentConfig = null;

    private string|int|null $currentTenantId = null;

    public function __construct(
        private readonly TenantRepository $tenantRepository,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Resolution
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Identify and bootstrap the tenant for the current request.
     *
     * Resolution strategy (first match wins):
     *  1. X-Tenant-ID header
     *  2. X-Tenant-Slug header
     *  3. Sub-domain (e.g. acme.api.example.com → "acme")
     *  4. JWT claim "tenant_id" (decoded without verification — Auth guard
     *     already validated the signature).
     *
     * @param  \Illuminate\Http\Request $request
     * @return string|int|null  Tenant ID, or null if not resolved
     */
    public function resolveFromRequest(\Illuminate\Http\Request $request): string|int|null
    {
        // 1. Explicit header
        if ($id = $request->header('X-Tenant-ID')) {
            return $this->bootstrapTenant((string) $id);
        }

        // 2. Slug header → resolve to ID
        if ($slug = $request->header('X-Tenant-Slug')) {
            $tenant = $this->tenantRepository->findBySlug($slug);
            if ($tenant) {
                return $this->bootstrapTenant((string) $tenant->id);
            }
        }

        // 3. Sub-domain
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            $tenant = $this->tenantRepository->findBySlug($subdomain);
            if ($tenant) {
                return $this->bootstrapTenant((string) $tenant->id);
            }
        }

        return null;
    }

    /**
     * Load a tenant's configuration and reconfigure the service at runtime.
     *
     * @param  string $tenantId
     * @return string  The tenant ID (passed through for chaining)
     */
    public function bootstrapTenant(string $tenantId): string
    {
        $config = $this->getTenantConfig($tenantId);

        $this->currentTenantId = $tenantId;
        $this->currentConfig   = $config;

        $this->applyDatabaseConfig($config);
        $this->applyCacheConfig($config);
        $this->applyQueueConfig($config);
        $this->applyMailConfig($config);

        return $tenantId;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Config accessors
    // ─────────────────────────────────────────────────────────────────────────

    public function getCurrentTenantId(): string|int|null
    {
        return $this->currentTenantId;
    }

    public function getCurrentConfig(): ?array
    {
        return $this->currentConfig;
    }

    /**
     * Return whether a feature flag is enabled for the current tenant.
     *
     * @param  string $flag
     * @param  bool   $default
     * @return bool
     */
    public function isFeatureEnabled(string $flag, bool $default = false): bool
    {
        return (bool) ($this->currentConfig['feature_flags'][$flag] ?? $default);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cache-backed config store
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retrieve a tenant's configuration, preferring Redis cache.
     *
     * @param  string $tenantId
     * @return array<string, mixed>
     */
    public function getTenantConfig(string $tenantId): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $tenantId;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($tenantId) {
            $tenant = $this->tenantRepository->findOrFail($tenantId);
            return $tenant->config ?? [];
        });
    }

    /**
     * Persist updated tenant config and flush the cache so subsequent
     * requests pick up the new values without restarting the service.
     *
     * @param  string               $tenantId
     * @param  array<string, mixed> $config
     * @return void
     */
    public function setTenantConfig(string $tenantId, array $config): void
    {
        $this->tenantRepository->updateConfig($tenantId, $config);

        // Flush cache entry so all pods pick up the new config within TTL
        Cache::forget(self::CACHE_KEY_PREFIX . $tenantId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Runtime configuration applicators
    // ─────────────────────────────────────────────────────────────────────────

    private function applyDatabaseConfig(array $config): void
    {
        if (empty($config['db_connection'])) {
            return;
        }

        $connection = $config['db_connection'];

        Config::set("database.connections.{$connection}", [
            'driver'    => $config['db_driver']   ?? 'mysql',
            'host'      => $config['db_host']      ?? '127.0.0.1',
            'port'      => $config['db_port']      ?? 3306,
            'database'  => $config['db_database'],
            'username'  => $config['db_username'],
            'password'  => $config['db_password'],
            'charset'   => $config['db_charset']  ?? 'utf8mb4',
            'collation' => $config['db_collation'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => $config['db_prefix']    ?? '',
            'strict'    => true,
            'engine'    => null,
        ]);

        // Purge any cached connection so DB::connection() uses the new config
        DB::purge($connection);
        DB::reconnect($connection);
    }

    private function applyCacheConfig(array $config): void
    {
        if (empty($config['cache_driver'])) {
            return;
        }

        Config::set('cache.default', $config['cache_driver']);

        if (!empty($config['cache_prefix'])) {
            Config::set('cache.prefix', $config['cache_prefix']);
        }
    }

    private function applyQueueConfig(array $config): void
    {
        if (empty($config['queue_driver'])) {
            return;
        }

        Config::set('queue.default', $config['queue_driver']);
    }

    private function applyMailConfig(array $config): void
    {
        if (empty($config['mail_driver'])) {
            return;
        }

        $mailKeys = ['driver', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name'];

        foreach ($mailKeys as $key) {
            $configKey = "mail_{$key}";
            if (isset($config[$configKey])) {
                Config::set("mail.{$key}", $config[$configKey]);
            }
        }
    }
}
