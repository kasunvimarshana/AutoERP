<?php
namespace App\Infrastructure\Cache;
use Illuminate\Support\Facades\Cache;

class InventoryCacheManager
{
    private string $prefix;

    public function __construct()
    {
        $this->prefix = config('cache.prefix', 'inventory_svc_');
    }

    private function key(string $tenantId, string ...$parts): string
    {
        return $this->prefix . "tenant:{$tenantId}:" . implode(':', $parts);
    }

    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): mixed
    {
        return Cache::store('redis')->get($this->key($tenantId, 'stock', $productId, $warehouseId));
    }

    public function setStockLevel(string $tenantId, string $productId, string $warehouseId, mixed $data): void
    {
        $ttl = config('inventory.cache.stock_level_ttl', 300);
        Cache::store('redis')->put($this->key($tenantId, 'stock', $productId, $warehouseId), $data, $ttl);
    }

    public function invalidateStockLevel(string $tenantId, string $productId, string $warehouseId): void
    {
        Cache::store('redis')->forget($this->key($tenantId, 'stock', $productId, $warehouseId));
    }

    public function getProduct(string $tenantId, string $productId): mixed
    {
        return Cache::store('redis')->get($this->key($tenantId, 'product', $productId));
    }

    public function setProduct(string $tenantId, string $productId, mixed $data): void
    {
        $ttl = config('inventory.cache.product_ttl', 600);
        Cache::store('redis')->put($this->key($tenantId, 'product', $productId), $data, $ttl);
    }

    public function invalidateProduct(string $tenantId, string $productId): void
    {
        Cache::store('redis')->forget($this->key($tenantId, 'product', $productId));
    }

    public function invalidateTenant(string $tenantId): void
    {
        // For pattern-based delete use Redis SCAN; predis supports this via client commands
        try {
            $redis   = Cache::store('redis')->getRedis();
            $pattern = $this->prefix . "tenant:{$tenantId}:*";
            $cursor  = null;
            do {
                [$cursor, $keys] = $redis->scan($cursor, ['match' => $pattern, 'count' => 100]);
                if (!empty($keys)) $redis->del($keys);
            } while ($cursor != 0);
        } catch (\Throwable) {}
    }
}
