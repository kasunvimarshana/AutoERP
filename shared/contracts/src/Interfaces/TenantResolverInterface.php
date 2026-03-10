<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Interfaces;

/**
 * TenantResolverInterface
 *
 * Resolves the current tenant context from an incoming request or
 * from an ambient header/token.  Each microservice must implement
 * this contract so the rest of the application never depends on a
 * concrete resolver.
 */
interface TenantResolverInterface
{
    /**
     * Return the tenant identifier for the current request context.
     *
     * @return string|int|null  Null when no tenant context is present
     *                          (e.g. during initial bootstrap calls).
     */
    public function resolve(): string|int|null;

    /**
     * Return all configuration values for a given tenant.
     *
     * The returned array must include at minimum:
     *  - db_connection, db_host, db_port, db_database, db_username, db_password
     *  - cache_driver, cache_prefix
     *  - queue_driver, queue_connection
     *  - mail_driver and associated mail settings
     *  - feature_flags  (associative array of flag => bool)
     *
     * @param  string|int $tenantId
     * @return array<string, mixed>
     */
    public function getConfig(string|int $tenantId): array;

    /**
     * Persist or refresh tenant configuration at runtime without requiring
     * a service restart or redeployment.
     *
     * @param  string|int           $tenantId
     * @param  array<string, mixed> $config
     * @return void
     */
    public function setConfig(string|int $tenantId, array $config): void;

    /**
     * Return whether a given feature flag is enabled for a tenant.
     *
     * @param  string|int $tenantId
     * @param  string     $flag
     * @return bool
     */
    public function isFeatureEnabled(string|int $tenantId, string $flag): bool;
}
