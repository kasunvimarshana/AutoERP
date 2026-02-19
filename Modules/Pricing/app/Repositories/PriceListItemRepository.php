<?php

declare(strict_types=1);

namespace Modules\Pricing\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Models\PriceListItem;

/**
 * PriceListItem Repository
 *
 * Handles data access for PriceListItem model
 */
class PriceListItemRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new PriceListItem;
    }

    /**
     * Get items for price list
     */
    public function getForPriceList(int $priceListId): Collection
    {
        return $this->model->newQuery()
            ->where('price_list_id', $priceListId)
            ->with('product')
            ->get();
    }

    /**
     * Find item for product in price list
     */
    public function findForProduct(int $priceListId, int $productId): ?PriceListItem
    {
        return $this->model->newQuery()
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * Find applicable tier for quantity
     */
    public function findApplicableTier(int $priceListId, int $productId, string $quantity): ?PriceListItem
    {
        return $this->model->newQuery()
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->forQuantity($quantity)
            ->orderBy('min_quantity', 'desc')
            ->first();
    }

    /**
     * Get all tiers for product
     */
    public function getTiersForProduct(int $priceListId, int $productId): Collection
    {
        return $this->model->newQuery()
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->orderBy('min_quantity', 'asc')
            ->get();
    }

    /**
     * Delete items for price list
     */
    public function deleteForPriceList(int $priceListId): bool
    {
        return $this->model->newQuery()
            ->where('price_list_id', $priceListId)
            ->delete();
    }

    /**
     * Delete items for product
     */
    public function deleteForProduct(int $priceListId, int $productId): bool
    {
        return $this->model->newQuery()
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->delete();
    }
}
