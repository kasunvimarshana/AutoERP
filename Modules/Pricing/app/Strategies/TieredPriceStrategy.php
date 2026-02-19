<?php

declare(strict_types=1);

namespace Modules\Pricing\Strategies;

use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Pricing\Repositories\PriceListItemRepository;
use Modules\Product\Models\Product;

/**
 * Tiered Price Strategy
 *
 * Volume-based pricing with quantity breaks
 */
class TieredPriceStrategy implements PricingEngineInterface
{
    public function __construct(
        private readonly PriceListItemRepository $priceListItemRepository
    ) {}

    /**
     * Calculate price using tiered pricing
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array
    {
        $product = Product::findOrFail($productId);
        $priceListId = $context['price_list_id'] ?? null;

        $basePrice = (string) $product->selling_price;
        $unitPrice = $basePrice;
        $tier = null;

        // Find applicable tier if price list is provided
        if ($priceListId) {
            $tier = $this->priceListItemRepository->findApplicableTier(
                $priceListId,
                $productId,
                $quantity
            );

            if ($tier) {
                $unitPrice = $tier->getFinalPrice();
            }
        }

        $subtotal = bcmul($unitPrice, $quantity, 2);

        return [
            'strategy' => $this->getStrategyName(),
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'currency' => $context['currency'] ?? 'USD',
            'breakdown' => [
                'base_price' => $basePrice,
                'tier_price' => $tier ? (string) $tier->price : null,
                'tier_discount' => $tier && $tier->discount_percentage ? (string) $tier->discount_percentage : null,
                'tier_min_qty' => $tier ? (string) $tier->min_quantity : null,
                'tier_max_qty' => $tier ? (string) $tier->max_quantity : null,
                'adjustments' => [],
            ],
        ];
    }

    /**
     * Get strategy name
     */
    public function getStrategyName(): string
    {
        return 'tiered';
    }
}
