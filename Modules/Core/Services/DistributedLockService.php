<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Cache\LockTimeoutException;

/**
 * Distributed Lock Service
 * 
 * Provides distributed locking mechanism using Redis for multi-server deployments.
 * Ensures atomic operations across horizontally scaled application instances.
 * 
 * Features:
 * - Atomic lock acquisition and release
 * - Automatic lock expiration
 * - Lock renewal for long-running operations
 * - Deadlock prevention
 * - Multi-tenant safe locking
 * 
 * @package Modules\Core\Services
 */
class DistributedLockService
{
    /**
     * Default lock timeout in seconds
     */
    private const DEFAULT_TIMEOUT = 10;

    /**
     * Default lock expiry in seconds
     */
    private const DEFAULT_EXPIRY = 30;

    /**
     * Lock key prefix for namespacing
     */
    private const LOCK_PREFIX = 'lock:';

    /**
     * Acquire a distributed lock
     * 
     * @param string $key Unique lock identifier
     * @param int $timeout Maximum seconds to wait for lock acquisition
     * @param int $expiry Lock expiration in seconds
     * @return bool True if lock acquired, false otherwise
     */
    public function acquire(string $key, int $timeout = self::DEFAULT_TIMEOUT, int $expiry = self::DEFAULT_EXPIRY): bool
    {
        $lockKey = $this->getLockKey($key);
        
        try {
            $lock = Cache::store('lock')->lock($lockKey, $expiry);
            $acquired = $lock->get(function () {
                // Lock acquired successfully
                return true;
            }, $timeout);

            if ($acquired) {
                Log::debug("Distributed lock acquired", [
                    'lock_key' => $lockKey,
                    'timeout' => $timeout,
                    'expiry' => $expiry,
                ]);
                return true;
            }

            Log::warning("Failed to acquire distributed lock", [
                'lock_key' => $lockKey,
                'timeout' => $timeout,
            ]);
            return false;

        } catch (LockTimeoutException $e) {
            Log::warning("Lock timeout exceeded", [
                'lock_key' => $lockKey,
                'timeout' => $timeout,
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error acquiring distributed lock", [
                'lock_key' => $lockKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Release a distributed lock
     * 
     * @param string $key Unique lock identifier
     * @return bool True if lock released, false otherwise
     */
    public function release(string $key): bool
    {
        $lockKey = $this->getLockKey($key);
        
        try {
            $lock = Cache::store('lock')->lock($lockKey);
            $result = $lock->forceRelease();

            if ($result) {
                Log::debug("Distributed lock released", [
                    'lock_key' => $lockKey,
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Error releasing distributed lock", [
                'lock_key' => $lockKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Execute a callback with distributed lock
     * 
     * @param string $key Unique lock identifier
     * @param callable $callback Function to execute within lock
     * @param int $timeout Maximum seconds to wait for lock
     * @param int $expiry Lock expiration in seconds
     * @return mixed Result from callback or null if lock not acquired
     * @throws \Exception If callback throws exception
     */
    public function executeWithLock(string $key, callable $callback, int $timeout = self::DEFAULT_TIMEOUT, int $expiry = self::DEFAULT_EXPIRY)
    {
        $lockKey = $this->getLockKey($key);
        
        try {
            $lock = Cache::store('lock')->lock($lockKey, $expiry);
            
            return $lock->get(function () use ($callback, $lockKey) {
                Log::debug("Executing callback with distributed lock", [
                    'lock_key' => $lockKey,
                ]);
                
                try {
                    return $callback();
                } catch (\Exception $e) {
                    Log::error("Error executing callback within lock", [
                        'lock_key' => $lockKey,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }, $timeout);

        } catch (LockTimeoutException $e) {
            Log::warning("Lock timeout while executing callback", [
                'lock_key' => $lockKey,
                'timeout' => $timeout,
            ]);
            return null;
        }
    }

    /**
     * Check if a lock exists
     * 
     * @param string $key Unique lock identifier
     * @return bool True if lock exists, false otherwise
     */
    public function exists(string $key): bool
    {
        $lockKey = $this->getLockKey($key);
        return Cache::store('lock')->has($lockKey);
    }

    /**
     * Get remaining time-to-live for a lock
     * 
     * @param string $key Unique lock identifier
     * @return int|null Seconds remaining, or null if lock doesn't exist
     */
    public function getTtl(string $key): ?int
    {
        $lockKey = $this->getLockKey($key);
        
        // Redis TTL command returns -1 if key exists but has no expiry, -2 if key doesn't exist
        $ttl = Cache::store('lock')->getStore()->getRedis()->ttl($lockKey);
        
        return $ttl > 0 ? $ttl : null;
    }

    /**
     * Renew lock expiration
     * 
     * Useful for long-running operations that need to extend the lock duration.
     * 
     * @param string $key Unique lock identifier
     * @param int $expiry New expiration in seconds
     * @return bool True if renewed, false otherwise
     */
    public function renew(string $key, int $expiry = self::DEFAULT_EXPIRY): bool
    {
        $lockKey = $this->getLockKey($key);
        
        try {
            if (!$this->exists($key)) {
                Log::warning("Cannot renew non-existent lock", [
                    'lock_key' => $lockKey,
                ]);
                return false;
            }

            Cache::store('lock')->put($lockKey, true, $expiry);
            
            Log::debug("Distributed lock renewed", [
                'lock_key' => $lockKey,
                'expiry' => $expiry,
            ]);
            
            return true;

        } catch (\Exception $e) {
            Log::error("Error renewing distributed lock", [
                'lock_key' => $lockKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate tenant-aware lock key
     * 
     * @param string $key Base lock identifier
     * @return string Prefixed and tenant-scoped lock key
     */
    private function getLockKey(string $key): string
    {
        $tenantId = session('tenant_id', 'global');
        return self::LOCK_PREFIX . $tenantId . ':' . $key;
    }

    /**
     * Acquire lock for stock operations
     * 
     * Specialized method for inventory stock operations requiring atomicity.
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return bool True if lock acquired
     */
    public function acquireStockLock(string $productId, string $warehouseId): bool
    {
        $key = "stock:{$productId}:{$warehouseId}";
        return $this->acquire($key, 5, 10);
    }

    /**
     * Release lock for stock operations
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return bool True if lock released
     */
    public function releaseStockLock(string $productId, string $warehouseId): bool
    {
        $key = "stock:{$productId}:{$warehouseId}";
        return $this->release($key);
    }

    /**
     * Execute stock operation with lock
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param callable $callback Operation to execute
     * @return mixed Result from callback
     */
    public function executeStockOperation(string $productId, string $warehouseId, callable $callback)
    {
        $key = "stock:{$productId}:{$warehouseId}";
        return $this->executeWithLock($key, $callback, 5, 10);
    }

    /**
     * Acquire lock for financial transactions
     * 
     * @param string $accountId Account identifier
     * @return bool True if lock acquired
     */
    public function acquireAccountLock(string $accountId): bool
    {
        $key = "account:{$accountId}";
        return $this->acquire($key, 10, 30);
    }

    /**
     * Release lock for financial transactions
     * 
     * @param string $accountId Account identifier
     * @return bool True if lock released
     */
    public function releaseAccountLock(string $accountId): bool
    {
        $key = "account:{$accountId}";
        return $this->release($key);
    }
}
