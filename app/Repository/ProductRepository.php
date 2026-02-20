<?php

namespace App\Repository;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Product);
    }

    public function findBySku(string $tenantId, string $sku): ?Product
    {
        /** @var Product|null */
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('sku', $sku)
            ->first();
    }

    public function findByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->with(['category', 'buyUnit', 'sellUnit']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('sku', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
