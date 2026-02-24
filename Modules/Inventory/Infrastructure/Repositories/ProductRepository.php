<?php
namespace Modules\Inventory\Infrastructure\Repositories;
use Modules\Inventory\Domain\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\ProductModel;
class ProductRepository implements ProductRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ProductModel::find($id);
    }
    public function findBySku(string $sku): ?object
    {
        return ProductModel::where('sku', $sku)->first();
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = ProductModel::query();
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['category_id'])) $query->where('category_id', $filters['category_id']);
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('sku', 'like', '%'.$filters['search'].'%')
                  ->orWhere('barcode_ean13', 'like', '%'.$filters['search'].'%');
            });
        }
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        return ProductModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $product = ProductModel::findOrFail($id);
        $product->update($data);
        return $product->fresh();
    }
    public function delete(string $id): bool
    {
        return ProductModel::findOrFail($id)->delete();
    }
}
