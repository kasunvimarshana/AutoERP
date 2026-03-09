<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Domain\Inventory\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Product Repository
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    protected array $searchableColumns = ['name', 'sku', 'description', 'category'];
    protected array $sortableColumns = ['name', 'sku', 'price', 'created_at', 'updated_at'];
    protected array $filterableColumns = ['tenant_id', 'category', 'is_active'];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku, string $tenantId): ?Product
    {
        return Product::where('sku', $sku)->where('tenant_id', $tenantId)->first();
    }

    public function findByTenant(string $tenantId, array $params = []): Collection|LengthAwarePaginator
    {
        $params['tenant_id'] = $tenantId;
        return $this->getAll($params);
    }

    public function searchProducts(string $query, string $tenantId, array $params = []): Collection|LengthAwarePaginator
    {
        $params['search'] = $query;
        $params['tenant_id'] = $tenantId;
        return $this->getAll($params);
    }
}
