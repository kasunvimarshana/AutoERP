<?php

namespace Modules\Inventory\Specifications;

use Modules\Core\Specifications\AbstractSpecification;
use Illuminate\Database\Eloquent\Builder;

/**
 * Product In Stock Specification
 * 
 * Filters products that have stock available
 * Useful for inventory availability queries
 */
class ProductInStockSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly ?string $warehouseId = null,
        private readonly int $minimumQuantity = 1
    ) {}

    public function apply(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('stockLedgerEntries', function ($ledgerQuery) {
                $ledgerQuery->selectRaw('product_id, SUM(quantity_change) as total_quantity')
                    ->groupBy('product_id')
                    ->havingRaw('SUM(quantity_change) >= ?', [$this->minimumQuantity]);

                if ($this->warehouseId) {
                    $ledgerQuery->where('warehouse_id', $this->warehouseId);
                }
            });
        });
    }
}

/**
 * Product By Category Specification
 * 
 * Filters products by category
 */
class ProductByCategorySpecification extends AbstractSpecification
{
    public function __construct(
        private readonly string $categoryId
    ) {}

    public function apply(Builder $query): Builder
    {
        return $query->where('category_id', $this->categoryId);
    }
}

/**
 * Product Active Specification
 * 
 * Filters only active products
 */
class ProductActiveSpecification extends AbstractSpecification
{
    public function apply(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

/**
 * Product By Price Range Specification
 * 
 * Filters products within a price range
 */
class ProductByPriceRangeSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly ?float $minPrice = null,
        private readonly ?float $maxPrice = null
    ) {}

    public function apply(Builder $query): Builder
    {
        if ($this->minPrice !== null) {
            $query->where('unit_price', '>=', $this->minPrice);
        }

        if ($this->maxPrice !== null) {
            $query->where('unit_price', '<=', $this->maxPrice);
        }

        return $query;
    }
}
