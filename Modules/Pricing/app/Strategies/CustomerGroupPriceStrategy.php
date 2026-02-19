<?php

declare(strict_types=1);

namespace Modules\Pricing\Strategies;

use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Pricing\Repositories\PriceListRepository;
use Modules\Product\Models\Product;

/**
 * Customer Group Price Strategy
 *
 * Pricing based on customer group
 */
class CustomerGroupPriceStrategy implements PricingEngineInterface
{
    public function __construct(
        private readonly PriceListRepository $priceListRepository
    ) {}

    /**
     * Calculate price using customer group pricing
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array
    {
        $product = Product::findOrFail($productId);
        $customerGroup = $context['customer_group'] ?? null;
        $customerId = $context['customer_id'] ?? null;

        $basePrice = (string) $product->selling_price;
        $unitPrice = $basePrice;
        $priceList = null;

        // Try customer-specific price list first
        if ($customerId) {
            $priceList = $this->priceListRepository->findActiveForCustomer($customerId);
        }

        // Fallback to customer group price list
        if (! $priceList && $customerGroup) {
            $priceList = $this->priceListRepository->findActiveForCustomerGroup($customerGroup);
        }

        if ($priceList) {
            $item = $priceList->items()->forProduct($productId)->first();
            if ($item) {
                $unitPrice = $item->getFinalPrice();
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
                'customer_id' => $customerId,
                'customer_group' => $customerGroup,
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
        return 'customer_group';
    }
}
