<?php

namespace Modules\Tenant\Infrastructure\Services;

use Modules\Tenant\Domain\Contracts\TenantConfigInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TenantConfigClient implements TenantConfigClientInterface
{
    protected string $tenantServiceUrl;
    protected int $cacheTtl; // seconds

    public function __construct(string $tenantServiceUrl, int $cacheTtl = 300)
    {
        $this->tenantServiceUrl = $tenantServiceUrl;
        $this->cacheTtl = $cacheTtl;
    }

    public function getConfig(int $tenantId): ?TenantConfigInterface
    {
        $cacheKey = "tenant_config_{$tenantId}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId) {
            $response = Http::get("{$this->tenantServiceUrl}/api/tenants/{$tenantId}");
            if (!$response->successful()) {
                return null;
            }
            $data = $response->json('data');
            return new TenantConfig($data); // implements TenantConfigInterface
        });
    }

    public function getConfigByDomain(string $domain): ?TenantConfigInterface
    {
        $cacheKey = "tenant_config_domain_{$domain}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($domain) {
            $response = Http::get("{$this->tenantServiceUrl}/api/config/domain/{$domain}");
            if (!$response->successful()) {
                return null;
            }
            $data = $response->json('data');
            return new TenantConfig($data);
        });
    }

    public function forgetCache(int $tenantId): void
    {
        Cache::forget("tenant_config_{$tenantId}");
    }
}
