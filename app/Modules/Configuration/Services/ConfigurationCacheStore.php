<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Configuration\Contracts\ConfigurationCacheInterface;

final class ConfigurationCacheStore implements ConfigurationCacheInterface
{
    private const INDEX_SUFFIX = '__keys';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $prefix,
        private readonly int $defaultTtlSeconds,
    ) {}

    public function has(string $key): bool
    {
        return $this->cache->has($this->key($key));
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($this->key($key));
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $ttl = $ttlSeconds ?? $this->defaultTtlSeconds;

        $resolvedKey = $this->key($key);
        $this->cache->put($resolvedKey, $value, $ttl);

        $keys = $this->trackedKeys();
        $keys[$resolvedKey] = true;
        $this->cache->put($this->indexKey(), array_keys($keys), $ttl);
    }

    public function forget(string $key): void
    {
        $resolvedKey = $this->key($key);
        $this->cache->forget($resolvedKey);

        $keys = $this->trackedKeys();
        unset($keys[$resolvedKey]);
        $this->cache->put($this->indexKey(), array_keys($keys), $this->defaultTtlSeconds);
    }

    public function flush(): void
    {
        foreach (array_keys($this->trackedKeys()) as $resolvedKey) {
            $this->cache->forget($resolvedKey);
        }

        $this->cache->forget($this->indexKey());
    }

    private function key(string $key): string
    {
        return $this->prefix.':'.$key;
    }

    /**
     * @return array<string, bool>
     */
    private function trackedKeys(): array
    {
        $raw = $this->cache->get($this->indexKey(), []);

        if (! is_array($raw)) {
            return [];
        }

        $map = [];
        foreach ($raw as $item) {
            if (is_string($item) && $item !== '') {
                $map[$item] = true;
            }
        }

        return $map;
    }

    private function indexKey(): string
    {
        return $this->prefix.':'.self::INDEX_SUFFIX;
    }
}
