<?php

namespace App\Modules\Pricing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Pricing\Models\PriceList;

class PriceListRepository extends BaseRepository
{
    /**
     * PriceListRepository constructor.
     */
    public function __construct(PriceList $model)
    {
        parent::__construct($model);
    }

    /**
     * Get default price list
     */
    public function getDefault(): ?PriceList
    {
        return $this->model
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active price lists
     */
    public function getActive(array $with = [])
    {
        $query = $this->model->where('is_active', true);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->orderBy('priority', 'desc')->get();
    }

    /**
     * Get valid price lists (active and within date range)
     */
    public function getValid(array $with = [])
    {
        $query = $this->model->valid();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->orderBy('priority', 'desc')->get();
    }

    /**
     * Get price list by code
     */
    public function findByCode(string $code): ?PriceList
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get price list by currency
     */
    public function getByCurrency(string $currency, array $with = [])
    {
        $query = $this->model
            ->where('currency', $currency)
            ->where('is_active', true);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->orderBy('priority', 'desc')->get();
    }

    /**
     * Get price for product
     */
    public function getPriceForProduct(
        int $productId,
        ?int $variantId = null,
        float $quantity = 1,
        ?int $priceListId = null
    ) {
        if ($priceListId) {
            $priceList = $this->findById($priceListId);
        } else {
            $priceList = $this->getDefault();
        }

        if (!$priceList) {
            return null;
        }

        return $priceList->getPriceForProduct($productId, $variantId, $quantity);
    }

    /**
     * Set as default price list
     */
    public function setAsDefault(int $id): bool
    {
        // Remove default flag from all other price lists
        $this->model->where('is_default', true)->update(['is_default' => false]);

        // Set this price list as default
        return $this->update($id, ['is_default' => true]);
    }
}
