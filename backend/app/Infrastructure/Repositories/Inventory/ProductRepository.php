<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Inventory;

use App\Domain\Inventory\Contracts\InventoryRepositoryInterface;
use App\Infrastructure\Repositories\BaseRepository;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Concrete Eloquent implementation of the Inventory repository.
 */
class ProductRepository extends BaseRepository implements InventoryRepositoryInterface
{
    protected array $filterable = [
        'category',
        'is_active',
        'is_trackable',
        'tenant_id',
    ];

    protected array $searchable = [
        'name',
        'sku',
        'description',
        'category',
    ];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): ?Model
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where('sku', strtoupper($sku))
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function adjustStock(int|string $productId, int $delta): Model
    {
        return DB::transaction(function () use ($productId, $delta): Model {
            /** @var Product $product */
            $product = $this->applyTenantScope($this->model->newQuery())
                ->lockForUpdate()
                ->findOrFail($productId);

            $newQuantity = $product->stock_quantity + $delta;

            if ($newQuantity < 0) {
                throw new \UnderflowException(
                    "Adjusting stock of product #{$productId} by {$delta} would result in "
                    . "negative stock ({$newQuantity})."
                );
            }

            $product->update(['stock_quantity' => $newQuantity]);

            return $product->fresh();
        });
    }

    public function findLowStock(): Collection
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->whereColumn('stock_quantity', '<=', 'reorder_point')
            ->where('is_trackable', true)
            ->get();
    }

    public function findByCategory(string $category): Collection
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where('category', $category)
            ->get();
    }

    public function bulkUpdateStock(array $stockMap): bool
    {
        return DB::transaction(function () use ($stockMap): bool {
            foreach ($stockMap as $productId => $newQuantity) {
                $this->applyTenantScope($this->model->newQuery())
                    ->where($this->model->getKeyName(), $productId)
                    ->update(['stock_quantity' => $newQuantity]);
            }

            return true;
        });
    }
}
