<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Product Repository Interface
 */
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySku(string $sku, string $tenantId): ?Product;
    public function findByTenant(string $tenantId, array $params = []): Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function searchProducts(string $query, string $tenantId, array $params = []): Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
