<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Contracts\TenantConfigInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class TenantConfigClient implements TenantConfigClientInterface
{
    private TenantRepositoryInterface $tenantRepository;

    protected int $cacheTtl; // seconds

    public function __construct(TenantRepositoryInterface $tenantRepository, int $cacheTtl = 300)
    {
        $this->tenantRepository = $tenantRepository;
        $this->cacheTtl = $cacheTtl;
    }

    public function getConfig(int $tenantId): ?TenantConfigInterface
    {
        return Cache::remember(
            $this->tenantIdCacheKey($tenantId),
            $this->cacheTtl,
            fn (): ?TenantConfigInterface => $this->configFromTenant($this->tenantRepository->find($tenantId))
        );
    }

    public function getConfigByDomain(string $domain): ?TenantConfigInterface
    {
        return Cache::remember(
            $this->tenantDomainCacheKey($domain),
            $this->cacheTtl,
            fn (): ?TenantConfigInterface => $this->configFromTenant($this->tenantRepository->findByDomain($domain))
        );
    }

    public function forgetCache(int $tenantId): void
    {
        Cache::forget($this->tenantIdCacheKey($tenantId));

        $tenant = $this->tenantRepository->find($tenantId);
        if ($tenant instanceof Tenant && $tenant->getDomain()) {
            Cache::forget($this->tenantDomainCacheKey($tenant->getDomain()));
        }
    }

    private function configFromTenant(mixed $tenant): ?TenantConfigInterface
    {
        if (! $tenant instanceof Tenant) {
            return null;
        }

        return new TenantConfig([
            'database_config' => $tenant->getDatabaseConfig()->toArray(),
            'mail_config' => $tenant->getMailConfig()?->toArray(),
            'cache_config' => $tenant->getCacheConfig()?->toArray(),
            'queue_config' => $tenant->getQueueConfig()?->toArray(),
            'feature_flags' => $tenant->getFeatureFlags()->toArray(),
            'api_keys' => $tenant->getApiKeys()->toArray(),
        ]);
    }

    private function tenantIdCacheKey(int $tenantId): string
    {
        return "tenant_config_{$tenantId}";
    }

    private function tenantDomainCacheKey(string $domain): string
    {
        return "tenant_config_domain_{$domain}";
    }
}
