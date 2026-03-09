<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Contracts\ProductRepositoryInterface;
use App\Domain\Entities\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Product Repository
 *
 * Extends BaseRepository to inherit generic CRUD + pagination logic.
 * Adds product-specific queries.
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    protected array $searchable = ['name', 'sku', 'description'];

    protected array $filterable = ['tenant_id', 'category_id', 'is_active'];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku, int|string $tenantId): ?Product
    {
        return $this->model
            ->where('sku', $sku)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByTenant(int|string $tenantId, array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->all(
            array_merge(['tenant_id' => $tenantId], $filters)
        );
    }

    public function findLowStock(int|string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->get();
    }

    public function adjustStock(int|string $productId, int $delta): Product
    {
        return DB::transaction(function () use ($productId, $delta) {
            $product = $this->model->lockForUpdate()->findOrFail($productId);

            $newQuantity = $product->quantity + $delta;

            if ($newQuantity < 0) {
                throw new \DomainException(
                    "Insufficient stock for product {$productId}. " .
                    "Available: {$product->quantity}, Requested: " . abs($delta)
                );
            }

            $product->update(['quantity' => $newQuantity]);
            return $product->fresh();
        });
    }
}
