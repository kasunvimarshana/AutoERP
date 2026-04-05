<?php
declare(strict_types=1);
namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class ResolvePriceService implements ResolvePriceServiceInterface
{
    public function __construct(
        private readonly PriceListItemRepositoryInterface $itemRepo,
    ) {}

    public function resolve(
        int $tenantId,
        int $priceListId,
        int $productId,
        float $basePrice,
        float $quantity,
        ?int $variantId = null,
    ): float {
        $items = $this->itemRepo->findForProduct($tenantId, $priceListId, $productId, $variantId);

        // Items are ordered by min_quantity ASC; find the highest applicable tier
        $applicable = null;
        foreach ($items as $item) {
            if ($quantity >= $item->getMinQuantity()) {
                $applicable = $item; // keep overwriting to get highest tier
            }
        }

        if ($applicable === null) {
            return $basePrice; // no matching tier — use base price
        }

        return $applicable->computePrice($basePrice, $quantity);
    }
}
