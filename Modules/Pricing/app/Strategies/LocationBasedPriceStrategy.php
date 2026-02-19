<?php

declare(strict_types=1);

namespace Modules\Pricing\Strategies;

use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Pricing\Repositories\PriceListRepository;
use Modules\Product\Models\Product;

/**
 * Location-Based Price Strategy
 *
 * Pricing based on location/branch
 */
class LocationBasedPriceStrategy implements PricingEngineInterface
{
    public function __construct(
        private readonly PriceListRepository $priceListRepository
    ) {}

    /**
     * Calculate price using location-based pricing
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array
    {
        $product = Product::findOrFail($productId);
        $locationCode = $context['location_code'] ?? null;

        $basePrice = (string) $product->selling_price;
        $unitPrice = $basePrice;
        $priceList = null;

        // Find location-specific price list
        if ($locationCode) {
            $priceList = $this->priceListRepository->findActiveForLocation($locationCode);

            if ($priceList) {
                $item = $priceList->items()->forProduct($productId)->first();
                if ($item) {
                    $unitPrice = $item->getFinalPrice();
                }
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
                'location_code' => $locationCode,
                'price_list_id' => $priceList?->id,
                'price_list_name' => $priceList?->name,
                'adjustments' => [],
            ],
        ];
    }

    /**
     * Get strategy name
     */
    public function getStrategyName(): string
    {
        return 'location_based';
    }
}
