<?php

declare(strict_types=1);

namespace Modules\Pricing\Strategies;

use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Product\Models\Product;

/**
 * Percentage Price Strategy
 *
 * Calculates price based on cost + percentage markup
 */
class PercentagePriceStrategy implements PricingEngineInterface
{
    /**
     * Calculate price using percentage markup
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array
    {
        $product = Product::findOrFail($productId);

        $costPrice = (string) $product->cost_price;
        $markupPercentage = $context['markup_percentage'] ?? '0';

        // Calculate markup amount
        $markupAmount = bcmul($costPrice, bcdiv($markupPercentage, '100', 4), 2);

        // Calculate unit price
        $unitPrice = bcadd($costPrice, $markupAmount, 2);

        // Calculate subtotal
        $subtotal = bcmul($unitPrice, $quantity, 2);

        return [
            'strategy' => $this->getStrategyName(),
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'currency' => $context['currency'] ?? 'USD',
            'breakdown' => [
                'base_price' => $costPrice,
                'markup_percentage' => $markupPercentage,
                'markup_amount' => $markupAmount,
                'adjustments' => [],
            ],
        ];
    }

    /**
     * Get strategy name
     */
    public function getStrategyName(): string
    {
        return 'percentage';
    }
}
