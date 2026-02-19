<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Services\BaseService;
use Modules\Pricing\Repositories\PriceListItemRepository;

/**
 * PriceListItem Service
 *
 * Contains business logic for PriceListItem operations
 */
class PriceListItemService extends BaseService
{
    public function __construct(PriceListItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get items for price list
     */
    public function getForPriceList(int $priceListId): mixed
    {
        return $this->repository->getForPriceList($priceListId);
    }

    /**
     * Find item for product
     */
    public function findForProduct(int $priceListId, int $productId): mixed
    {
        return $this->repository->findForProduct($priceListId, $productId);
    }

    /**
     * Get all tiers for product
     */
    public function getTiersForProduct(int $priceListId, int $productId): mixed
    {
        return $this->repository->getTiersForProduct($priceListId, $productId);
    }

    /**
     * Bulk update items for price list
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function bulkUpdateItems(int $priceListId, array $items): void
    {
        // Delete existing items
        $this->repository->deleteForPriceList($priceListId);

        // Create new items
        foreach ($items as $item) {
            $item['price_list_id'] = $priceListId;
            $this->repository->create($item);
        }
    }
}
