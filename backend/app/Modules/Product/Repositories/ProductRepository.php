<?php
namespace App\Modules\Product\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Product;

class ProductRepository implements RepositoryInterface
{
    public function __construct(private Product $model) {}

    public function all(array $filters = [], array $relations = [])
    {
        $query = $this->model->newQuery()->with($relations);

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (isset($filters['search'])) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('description', 'like', "%{$filters['search']}%")
                ->orWhere('sku', 'like', "%{$filters['search']}%")
            );
        }
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        return $query;
    }

    public function find(int $id, array $relations = []): ?Product
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->find($id);
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $filters = [], array $relations = [])
    {
        return $this->all($filters, $relations)->paginate($perPage);
    }

    public function findBySku(string $sku, int $tenantId): ?Product
    {
        return $this->model->where('sku', $sku)->where('tenant_id', $tenantId)->first();
    }
}
