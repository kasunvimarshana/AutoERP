<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\Product;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class PricingService extends BaseService
{
    /**
     * Calculate price for a product with optional rules
     */
    public function calculatePrice(
        int $productId,
        float $quantity = 1,
        ?int $customerId = null,
        array $options = []
    ): array {
        $product = Product::findOrFail($productId);

        // Base price
        $unitPrice = $product->selling_price;
        $subtotal = $unitPrice * $quantity;

        // Apply discounts
        $discountAmount = 0;
        $discountPercentage = 0;

        // 1. Product-level discount
        if ($product->discount_type === 'percentage' && $product->discount_value > 0) {
            $discountPercentage = $product->discount_value;
            $discountAmount = ($subtotal * $discountPercentage) / 100;
        } elseif ($product->discount_type === 'fixed' && $product->discount_value > 0) {
            $discountAmount = min($product->discount_value * $quantity, $subtotal);
        }

        // 2. Tiered pricing based on quantity
        if (! empty($options['apply_tiered_pricing'])) {
            $tieredDiscount = $this->calculateTieredDiscount($product, $quantity, $subtotal);
            $discountAmount = max($discountAmount, $tieredDiscount);
        }

        // 3. Customer-specific pricing
        if ($customerId && ! empty($options['apply_customer_pricing'])) {
            $customerDiscount = $this->calculateCustomerDiscount($productId, $customerId, $subtotal);
            $discountAmount += $customerDiscount;
        }

        // Calculate totals
        $afterDiscount = $subtotal - $discountAmount;

        // Calculate tax
        $taxAmount = 0;
        $taxRate = 0;

        if ($product->tax_id) {
            $tax = DB::table('taxes')->find($product->tax_id);
            if ($tax && $tax->is_active) {
                $taxRate = $tax->rate;
                if ($tax->type === 'percentage') {
                    $taxAmount = ($afterDiscount * $taxRate) / 100;
                } else {
                    $taxAmount = $taxRate * $quantity;
                }
            }
        }

        // Final total
        $total = $afterDiscount + $taxAmount;

        return [
            'unit_price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_percentage' => round($discountPercentage, 2),
            'after_discount' => round($afterDiscount, 2),
            'tax_rate' => round($taxRate, 4),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
            'profit_margin' => $this->calculateProfitMargin($product, $total, $quantity),
        ];
    }

    /**
     * Calculate tiered discount based on quantity
     */
    protected function calculateTieredDiscount(Product $product, float $quantity, float $subtotal): float
    {
        // Tiered pricing rules would be stored in a separate table
        // For now, this is a simple implementation
        $tiers = [
            ['min_qty' => 10, 'discount_percentage' => 5],
            ['min_qty' => 50, 'discount_percentage' => 10],
            ['min_qty' => 100, 'discount_percentage' => 15],
        ];

        $applicableDiscount = 0;
        foreach ($tiers as $tier) {
            if ($quantity >= $tier['min_qty']) {
                $applicableDiscount = $tier['discount_percentage'];
            }
        }

        return ($subtotal * $applicableDiscount) / 100;
    }

    /**
     * Calculate customer-specific discount
     */
    protected function calculateCustomerDiscount(int $productId, int $customerId, float $subtotal): float
    {
        // Customer-specific pricing would be stored in a separate table
        // This is a placeholder for future implementation
        return 0;
    }

    /**
     * Calculate profit margin
     */
    protected function calculateProfitMargin(Product $product, float $sellingPrice, float $quantity): array
    {
        $costPrice = $product->buying_price * $quantity;
        $profit = $sellingPrice - $costPrice;
        $profitPercentage = $costPrice > 0 ? ($profit / $costPrice) * 100 : 0;

        return [
            'cost_price' => round($costPrice, 2),
            'profit' => round($profit, 2),
            'profit_percentage' => round($profitPercentage, 2),
        ];
    }

    /**
     * Apply additional discount to calculated price
     *
     * @param  string  $type  (percentage or fixed)
     */
    public function applyAdditionalDiscount(array $priceData, float $additionalDiscount, string $type = 'percentage'): array
    {
        $currentTotal = $priceData['after_discount'];
        $extraDiscount = 0;

        if ($type === 'percentage') {
            $extraDiscount = ($currentTotal * $additionalDiscount) / 100;
        } else {
            $extraDiscount = min($additionalDiscount, $currentTotal);
        }

        $newAfterDiscount = $currentTotal - $extraDiscount;
        $newTaxAmount = ($newAfterDiscount * $priceData['tax_rate']) / 100;
        $newTotal = $newAfterDiscount + $newTaxAmount;

        return [
            ...$priceData,
            'additional_discount' => round($extraDiscount, 2),
            'total_discount' => round($priceData['discount_amount'] + $extraDiscount, 2),
            'after_discount' => round($newAfterDiscount, 2),
            'tax_amount' => round($newTaxAmount, 2),
            'total' => round($newTotal, 2),
        ];
    }
}
