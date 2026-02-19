<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Cache Manager Interface
 *
 * Contract for cache management to ensure consistent caching
 * strategies across modules.
 */
interface CacheManagerInterface
{
    /**
     * Get a value from cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if not found
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|null  $ttl  Time to live in seconds (null for default)
     * @return bool True if successful
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Check if key exists in cache.
     *
     * @param  string  $key  Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Remove a value from cache.
     *
     * @param  string  $key  Cache key
     * @return bool True if successful
     */
    public function forget(string $key): bool;

    /**
     * Clear all cache.
     *
     * @return bool True if successful
     */
    public function flush(): bool;

    /**
     * Get value or compute and cache it.
     *
     * @param  string  $key  Cache key
     * @param  callable  $callback  Callback to compute value
     * @param  int|null  $ttl  Time to live in seconds
     * @return mixed Cached or computed value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed;

    /**
     * Invalidate cache tags.
     *
     * @param  array  $tags  Tags to invalidate
     * @return bool True if successful
     */
    public function invalidateTags(array $tags): bool;
}
