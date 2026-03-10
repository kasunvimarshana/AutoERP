<?php

declare(strict_types=1);

namespace KvSaas\Contracts\DTOs;

/**
 * TenantContextDTO
 *
 * Immutable value object that carries the tenant context through the
 * request lifecycle.  All services depend on this DTO, not on raw
 * HTTP headers or session values.
 */
final class TenantContextDTO
{
    public function __construct(
        public readonly string|int          $tenantId,
        public readonly string              $tenantSlug,
        public readonly array               $config       = [],
        public readonly array               $featureFlags = [],
        public readonly string|null         $dbConnection = null,
        public readonly string|null         $cacheDriver  = null,
        public readonly string|null         $queueDriver  = null,
    ) {}

    /**
     * Create from an array (e.g. from Redis cache).
     *
     * @param  array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId:     $data['tenant_id'],
            tenantSlug:   $data['tenant_slug'],
            config:       $data['config']        ?? [],
            featureFlags: $data['feature_flags'] ?? [],
            dbConnection: $data['db_connection']  ?? null,
            cacheDriver:  $data['cache_driver']   ?? null,
            queueDriver:  $data['queue_driver']   ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'     => $this->tenantId,
            'tenant_slug'   => $this->tenantSlug,
            'config'        => $this->config,
            'feature_flags' => $this->featureFlags,
            'db_connection' => $this->dbConnection,
            'cache_driver'  => $this->cacheDriver,
            'queue_driver'  => $this->queueDriver,
        ];
    }
}
