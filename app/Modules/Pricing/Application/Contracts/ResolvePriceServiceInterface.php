<?php
declare(strict_types=1);
namespace Modules\Pricing\Application\Contracts;

interface ResolvePriceServiceInterface
{
    /**
     * Resolve the effective unit price for a product/variant from a price list,
     * taking quantity tiers into account.
     *
     * @return float The effective unit price (in the price list's currency).
     */
    public function resolve(
        int $tenantId,
        int $priceListId,
        int $productId,
        float $basePrice,
        float $quantity,
        ?int $variantId = null,
    ): float;
}
