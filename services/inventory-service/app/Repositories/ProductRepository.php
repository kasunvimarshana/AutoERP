<?php

namespace App\Repositories;

use App\Domain\Contracts\ProductRepositoryInterface;
use App\Domain\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    protected array $searchableFields = ['name', 'sku', 'description', 'barcode'];
    protected array $with = [];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    protected function getAllowedFilterFields(): array
    {
        return [
            'tenant_id', 'category_id', 'sku', 'name', 'is_active',
            'unit_price', 'cost_price', 'minimum_stock', 'reorder_point',
            'barcode', 'unit_of_measure',
        ];
    }

    protected function getAllowedSortFields(): array
    {
        return ['name', 'sku', 'unit_price', 'cost_price', 'created_at', 'updated_at', 'minimum_stock'];
    }

    protected function getAllowedRelations(): array
    {
        return ['category', 'stockLevels', 'stockLevels.warehouse'];
    }

    // -------------------------------------------------------------------------

    public function findById(string $tenantId, string $id): ?object
    {
        return $this->model
            ->byTenant($tenantId)
            ->with(['category', 'stockLevels.warehouse'])
            ->find($id);
    }

    public function findBySku(string $tenantId, string $sku): ?object
    {
        return $this->model
            ->byTenant($tenantId)
            ->bySku($sku)
            ->with(['category', 'stockLevels.warehouse'])
            ->first();
    }

    public function findByCategory(string $tenantId, string $categoryId, array $params = []): mixed
    {
        $params['filter']['category_id'] = $categoryId;
        return $this->list($tenantId, $params);
    }

    public function findLowStock(string $tenantId, ?int $threshold = null): mixed
    {
        return $this->model
            ->byTenant($tenantId)
            ->active()
            ->with(['stockLevels.warehouse', 'category'])
            ->whereHas('stockLevels', function (Builder $q) use ($threshold) {
                if ($threshold !== null) {
                    $q->where('quantity_available', '<=', $threshold);
                } else {
                    $q->whereRaw('quantity_available <= products.reorder_point');
                }
            })
            ->get();
    }

    public function searchByNameOrSku(string $tenantId, string $query, array $params = []): mixed
    {
        $params['search'] = $query;
        return $this->list($tenantId, $params);
    }

    public function list(string $tenantId, array $params = []): mixed
    {
        $params['filter']['tenant_id'] = $tenantId;
        return $this->query($params);
    }

    public function existsBySku(string $tenantId, string $sku, ?string $excludeId = null): bool
    {
        $q = $this->model
            ->byTenant($tenantId)
            ->bySku($sku);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->exists();
    }

    protected function applyDefaultSort(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }
}
