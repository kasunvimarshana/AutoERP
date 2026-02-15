<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Product\Models\Product;
use App\Models\PricingRule;
use App\Models\DiscountTier;
use App\Core\DTOs\Product\ProductPricingDTO;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Pricing Service
 * 
 * Handles all pricing calculations including dynamic rules, discounts, and taxes
 */
class PricingService
{
    /**
     * Calculate final price for a product with all applicable rules
     *
     * @param int $productId
     * @param float $quantity
     * @param array $context
     * @return array
     */
    public function calculatePrice(int $productId, float $quantity = 1.0, array $context = []): array
    {
        $product = $this->getProduct($productId);
        
        if (!$product) {
            throw new ServiceException("Product not found: {$productId}");
        }

        // Get base price (ensure it's a float)
        $basePrice = (float) $product->selling_price;
        
        // Get applicable pricing rules
        $applicableRules = $this->getApplicablePricingRules($product, $quantity, $context);
        
        // Apply pricing rules
        $ruledPrice = $this->applyPricingRules($basePrice, $applicableRules);
        
        // Get applicable discount tiers
        $applicableTier = $this->getApplicableDiscountTier($product, $quantity, $context);
        
        // Apply discount tier if found
        $discountedPrice = $applicableTier 
            ? $applicableTier->calculateDiscountedPrice($ruledPrice)
            : $ruledPrice;
        
        // Apply product-level discounts if no tier discount applied
        if (!$applicableTier && $product->selling_discount_type !== 'none') {
            $discountedPrice = $this->applyProductDiscount(
                $discountedPrice,
                $product->selling_discount_type,
                (float) $product->selling_discount_value
            );
        }
        
        // Create pricing DTO
        $pricingDTO = new ProductPricingDTO(
            productId: $productId,
            quantity: $quantity,
            basePrice: $basePrice,
            buyingPrice: $product->buying_price ? (float) $product->buying_price : null,
            discountType: $applicableTier ? $applicableTier->discount_type : $product->selling_discount_type,
            discountValue: $applicableTier ? (float) $applicableTier->discount_value : (float) $product->selling_discount_value,
            taxRateId: $product->tax_rate_id,
            taxRate: $product->taxRate ? (float) $product->taxRate->rate : null,
            isTaxInclusive: $product->is_tax_inclusive,
            profitMarginType: $product->profit_margin_type,
            profitMarginValue: $product->profit_margin_value ? (float) $product->profit_margin_value : null,
            context: array_merge($context, [
                'applied_rules' => $applicableRules->pluck('id')->toArray(),
                'applied_tier' => $applicableTier ? $applicableTier->id : null,
            ])
        );
        
        // Calculate final price with all adjustments
        $finalPricing = $pricingDTO->calculateFinalPrice();
        
        // Add additional context
        $finalPricing['product'] = [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'type' => $product->type,
        ];
        
        $finalPricing['applied_rules'] = $applicableRules->map(function ($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'type' => $rule->rule_type,
                'priority' => $rule->priority,
            ];
        });
        
        if ($applicableTier) {
            $finalPricing['applied_tier'] = [
                'id' => $applicableTier->id,
                'label' => $applicableTier->label,
                'min_quantity' => $applicableTier->min_quantity,
                'max_quantity' => $applicableTier->max_quantity,
                'discount_type' => $applicableTier->discount_type,
                'discount_value' => $applicableTier->discount_value,
            ];
        }
        
        return $finalPricing;
    }

    /**
     * Calculate bulk pricing for multiple products
     *
     * @param array $items [['product_id' => 1, 'quantity' => 5], ...]
     * @param array $context
     * @return array
     */
    public function calculateBulkPricing(array $items, array $context = []): array
    {
        $results = [];
        $totalAmount = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $pricing = $this->calculatePrice(
                $item['product_id'],
                $item['quantity'] ?? 1.0,
                array_merge($context, $item['context'] ?? [])
            );

            $results[] = $pricing;
            $totalAmount += $pricing['total_amount'];
            $totalTax += $pricing['tax_amount'];
            $totalDiscount += $pricing['discount_amount'];
        }

        return [
            'items' => $results,
            'summary' => [
                'subtotal' => $totalAmount - $totalTax,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'grand_total' => $totalAmount,
                'item_count' => count($items),
            ],
        ];
    }

    /**
     * Get applicable pricing rules for a product
     *
     * @param Product $product
     * @param float $quantity
     * @param array $context
     * @return Collection
     */
    protected function getApplicablePricingRules(Product $product, float $quantity, array $context): Collection
    {
        $customerId = $context['customer_id'] ?? null;
        $customerGroup = $context['customer_group'] ?? null;

        return PricingRule::query()
            ->active()
            ->currentlyValid()
            ->where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere('category_id', $product->category_id)
                    ->orWhere(function ($q) {
                        $q->whereNull('product_id')
                          ->whereNull('category_id');
                    });
            })
            ->forCustomer($customerId, $customerGroup)
            ->where(function ($query) use ($quantity) {
                $query->where('min_quantity', '<=', $quantity)
                    ->where(function ($q) use ($quantity) {
                        $q->whereNull('max_quantity')
                          ->orWhere('max_quantity', '>=', $quantity);
                    });
            })
            ->byPriority('desc')
            ->get()
            ->filter(fn($rule) => $rule->isCurrentlyValid() && $rule->appliesToQuantity($quantity));
    }

    /**
     * Get applicable discount tier for a product
     *
     * @param Product $product
     * @param float $quantity
     * @param array $context
     * @return DiscountTier|null
     */
    protected function getApplicableDiscountTier(Product $product, float $quantity, array $context): ?DiscountTier
    {
        return DiscountTier::query()
            ->forProduct($product->id)
            ->byType('selling')
            ->orderByQuantity('desc')
            ->get()
            ->first(fn($tier) => $tier->appliesToQuantity($quantity));
    }

    /**
     * Apply pricing rules to base price
     *
     * @param float $basePrice
     * @param Collection $rules
     * @return float
     */
    protected function applyPricingRules(float $basePrice, Collection $rules): float
    {
        $price = $basePrice;

        foreach ($rules as $rule) {
            if ($rule->can_compound) {
                // Apply rule to current price
                $price = $rule->calculateAdjustedPrice($price);
            } else {
                // Apply rule to base price and take best
                $adjustedPrice = $rule->calculateAdjustedPrice($basePrice);
                $price = min($price, $adjustedPrice);
            }
        }

        return $price;
    }

    /**
     * Apply product-level discount
     *
     * @param float $price
     * @param string $discountType
     * @param float $discountValue
     * @return float
     */
    protected function applyProductDiscount(float $price, string $discountType, float $discountValue): float
    {
        if ($discountType === 'flat') {
            return max(0, $price - $discountValue);
        }

        if ($discountType === 'percentage') {
            return max(0, $price - ($price * $discountValue / 100));
        }

        return $price;
    }

    /**
     * Get product with relationships
     *
     * @param int $productId
     * @return Product|null
     */
    protected function getProduct(int $productId): ?Product
    {
        return Cache::remember(
            "product.{$productId}",
            now()->addMinutes(5),
            fn() => Product::with(['taxRate', 'category'])->find($productId)
        );
    }

    /**
     * Clear product cache
     *
     * @param int $productId
     * @return void
     */
    public function clearProductCache(int $productId): void
    {
        Cache::forget("product.{$productId}");
    }
}
