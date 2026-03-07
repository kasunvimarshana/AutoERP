<?php

namespace App\Services;

use App\DTOs\TenantConfigDTO;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Loads and merges tenant-specific runtime configuration.
 *
 * Tenant config is stored as a JSON blob on the tenants.config column.
 * Supported sections: mail, payment, notifications, features, limits.
 */
class TenantConfigService
{
    private const CACHE_PREFIX = 'tenant_config:';
    private const CACHE_TTL    = 300; // seconds

    // -------------------------------------------------------------------------
    // Main entry point
    // -------------------------------------------------------------------------

    public function getConfig(int|string $tenantId): TenantConfigDTO
    {
        return Cache::remember(
            self::CACHE_PREFIX . $tenantId,
            self::CACHE_TTL,
            function () use ($tenantId): TenantConfigDTO {
                $tenant = Tenant::findOrFail($tenantId);

                return TenantConfigDTO::fromArray((array) ($tenant->config ?? []));
            }
        );
    }

    public function refreshCache(int|string $tenantId): void
    {
        Cache::forget(self::CACHE_PREFIX . $tenantId);
    }

    // -------------------------------------------------------------------------
    // Section helpers
    // -------------------------------------------------------------------------

    public function getMailConfig(int|string $tenantId): array
    {
        return $this->getConfig($tenantId)->mail;
    }

    public function getPaymentConfig(int|string $tenantId): array
    {
        return $this->getConfig($tenantId)->payment;
    }

    public function getNotificationConfig(int|string $tenantId): array
    {
        return $this->getConfig($tenantId)->notifications;
    }

    public function getFeatureFlags(int|string $tenantId): array
    {
        return $this->getConfig($tenantId)->features;
    }

    public function getLimits(int|string $tenantId): array
    {
        return $this->getConfig($tenantId)->limits;
    }

    // -------------------------------------------------------------------------
    // Runtime config overrides
    // -------------------------------------------------------------------------

    /**
     * Dynamically configure the Laravel mail driver for the active tenant.
     */
    public function applyMailConfig(int|string $tenantId): void
    {
        $mail = $this->getMailConfig($tenantId);

        if (empty($mail)) {
            return;
        }

        $driver = $mail['driver'] ?? config('mail.default');

        config([
            'mail.default'                       => $driver,
            'mail.mailers.' . $driver . '.host'  => $mail['host']     ?? config("mail.mailers.{$driver}.host"),
            'mail.mailers.' . $driver . '.port'  => $mail['port']     ?? config("mail.mailers.{$driver}.port"),
            'mail.mailers.' . $driver . '.username' => $mail['username'] ?? config("mail.mailers.{$driver}.username"),
            'mail.mailers.' . $driver . '.password' => $mail['password'] ?? config("mail.mailers.{$driver}.password"),
            'mail.from.address'                  => $mail['from_address'] ?? config('mail.from.address'),
            'mail.from.name'                     => $mail['from_name']    ?? config('mail.from.name'),
        ]);

        Log::debug('Applied tenant mail config', ['tenant_id' => $tenantId, 'driver' => $driver]);
    }

    /**
     * Update the tenant config in the database and purge the cache.
     */
    public function updateConfig(int|string $tenantId, array $config): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        $existing = (array) ($tenant->config ?? []);
        $merged   = array_replace_recursive($existing, $config);

        $tenant->update(['config' => $merged]);
        $this->refreshCache($tenantId);

        Log::info('Tenant config updated', ['tenant_id' => $tenantId]);
    }

    // -------------------------------------------------------------------------
    // Feature flag check
    // -------------------------------------------------------------------------

    public function isFeatureEnabled(int|string $tenantId, string $feature): bool
    {
        $flags = $this->getFeatureFlags($tenantId);

        return (bool) ($flags[$feature] ?? false);
    }

    // -------------------------------------------------------------------------
    // Limit check
    // -------------------------------------------------------------------------

    public function getLimit(int|string $tenantId, string $limitKey, mixed $default = null): mixed
    {
        $limits = $this->getLimits($tenantId);

        return $limits[$limitKey] ?? $default;
    }
}
