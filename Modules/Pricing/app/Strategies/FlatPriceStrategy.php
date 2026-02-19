<?php

declare(strict_types=1);

namespace Modules\Pricing\Strategies;

use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Product\Models\Product;

/**
 * Flat Price Strategy
 *
 * Uses fixed product selling price
 */
class FlatPriceStrategy implements PricingEngineInterface
{
    /**
     * Calculate price using flat pricing
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array
    {
        $product = Product::findOrFail($productId);

        $unitPrice = (string) $product->selling_price;
        $subtotal = bcmul($unitPrice, $quantity, 2);

        return [
            'strategy' => $this->getStrategyName(),
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'currency' => $context['currency'] ?? 'USD',
            'breakdown' => [
                'base_price' => $unitPrice,
                'adjustments' => [],
            ],
        ];
    }

    /**
     * Get strategy name
     */
    public function getStrategyName(): string
    {
        return 'flat';
    }
}
