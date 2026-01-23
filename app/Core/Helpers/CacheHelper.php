<?php

declare(strict_types=1);

namespace App\Core\Helpers;

use App\Core\Enums\CacheDuration;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Helper
 *
 * Provides caching utilities with tenant awareness
 */
final class CacheHelper
{
    /**
     * Cache key prefix
     *
     * @var string
     */
    private const PREFIX = 'app';

    /**
     * Generate a cache key
     */
    public static function key(string $key, ?string $tenantId = null): string
    {
        $parts = [self::PREFIX];

        if ($tenantId) {
            $parts[] = "tenant:{$tenantId}";
        }

        $parts[] = $key;

        return implode(':', $parts);
    }

    /**
     * Get a value from cache
     */
    public static function get(string $key, mixed $default = null, ?string $tenantId = null): mixed
    {
        return Cache::get(self::key($key, $tenantId), $default);
    }

    /**
     * Store a value in cache
     *
     * @param  CacheDuration|int|null  $ttl  Time to live in seconds or CacheDuration enum
     */
    public static function put(
        string $key,
        mixed $value,
        CacheDuration|int|null $ttl = null,
        ?string $tenantId = null
    ): bool {
        $seconds = $ttl instanceof CacheDuration ? $ttl->value : $ttl;

        if ($seconds === null) {
            return Cache::forever(self::key($key, $tenantId), $value);
        }

        return Cache::put(self::key($key, $tenantId), $value, $seconds);
    }

    /**
     * Remember a value in cache
     */
    public static function remember(
        string $key,
        CacheDuration|int $ttl,
        callable $callback,
        ?string $tenantId = null
    ): mixed {
        $seconds = $ttl instanceof CacheDuration ? $ttl->value : $ttl;

        return Cache::remember(self::key($key, $tenantId), $seconds, $callback);
    }

    /**
     * Remove a value from cache
     */
    public static function forget(string $key, ?string $tenantId = null): bool
    {
        return Cache::forget(self::key($key, $tenantId));
    }

    /**
     * Clear all cache for a tenant
     */
    public static function clearTenant(string $tenantId): void
    {
        $pattern = self::key('*', $tenantId);
        Cache::flush(); // In production, use more specific cache clearing
    }

    /**
     * Check if a cache key exists
     */
    public static function has(string $key, ?string $tenantId = null): bool
    {
        return Cache::has(self::key($key, $tenantId));
    }

    /**
     * Increment a numeric value in cache
     */
    public static function increment(string $key, int $value = 1, ?string $tenantId = null): int|bool
    {
        return Cache::increment(self::key($key, $tenantId), $value);
    }

    /**
     * Decrement a numeric value in cache
     */
    public static function decrement(string $key, int $value = 1, ?string $tenantId = null): int|bool
    {
        return Cache::decrement(self::key($key, $tenantId), $value);
    }
}
