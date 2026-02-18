<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Repositories\Contracts\ProductRepositoryInterface;

/**
 * Product Repository Implementation
 *
 * Handles all product data access operations.
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    /**
     * ProductRepository constructor.
     */
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return $this->model->where('barcode', $barcode)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveProducts(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model
            ->where('product_category_id', $categoryId)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('sku', 'LIKE', "%{$query}%")
            ->orWhere('barcode', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getStockableProducts(): Collection
    {
        return $this->model
            ->where('product_type', 'stockable')
            ->where('status', 'active')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsNeedingReorder(): Collection
    {
        return $this->model
            ->where('status', 'active')
            ->whereNotNull('reorder_level')
            ->whereHas('stockLevels')
            ->get()
            ->filter(function ($product) {
                return $product->needsReorder();
            });
    }
}
