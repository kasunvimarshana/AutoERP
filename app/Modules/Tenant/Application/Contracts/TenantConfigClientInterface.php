<?php

namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Domain\Contracts\TenantConfigInterface;

interface TenantConfigClientInterface
{
    /**
     * Fetch (and cache) the configuration for a given tenant ID.
     */
    public function getConfig(int $tenantId): ?TenantConfigInterface;

    /**
     * Fetch (and cache) the configuration for a given domain.
     */
    public function getConfigByDomain(string $domain): ?TenantConfigInterface;

    /**
     * Invalidate the cached configuration for a given tenant ID.
     */
    public function forgetCache(int $tenantId): void;
}
