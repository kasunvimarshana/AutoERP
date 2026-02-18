<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    protected function getTenantPrefix(): string
    {
        $tenantId = $this->tenantContext->getTenantId();

        return $tenantId ? "tenant:{$tenantId}:" : 'global:';
    }

    public function get(string $key, $default = null)
    {
        return Cache::get($this->getTenantPrefix().$key, $default);
    }

    public function put(string $key, $value, $ttl = null): bool
    {
        return Cache::put($this->getTenantPrefix().$key, $value, $ttl);
    }

    public function remember(string $key, $ttl, callable $callback)
    {
        return Cache::remember($this->getTenantPrefix().$key, $ttl, $callback);
    }

    public function rememberForever(string $key, callable $callback)
    {
        return Cache::rememberForever($this->getTenantPrefix().$key, $callback);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($this->getTenantPrefix().$key);
    }

    public function has(string $key): bool
    {
        return Cache::has($this->getTenantPrefix().$key);
    }

    public function flush(): bool
    {
        $prefix = $this->getTenantPrefix();

        if ($prefix === 'global:') {
            return Cache::flush();
        }

        return Cache::tags([$prefix])->flush();
    }

    public function tags(array $tags)
    {
        $tenantTags = array_map(
            fn ($tag) => $this->getTenantPrefix().$tag,
            $tags
        );

        return Cache::tags($tenantTags);
    }
}
