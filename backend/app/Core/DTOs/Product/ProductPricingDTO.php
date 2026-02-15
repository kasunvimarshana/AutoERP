<?php

declare(strict_types=1);

namespace App\Core\DTOs\Product;

use App\Core\DTOs\BaseDTO;

/**
 * Product Pricing Data Transfer Object
 * 
 * Handles pricing calculations with discounts, taxes, and profit margins
 */
class ProductPricingDTO extends BaseDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly float $quantity,
        public readonly float $basePrice,
        public readonly ?float $buyingPrice = null,
        public readonly ?string $discountType = null,
        public readonly ?float $discountValue = null,
        public readonly ?int $taxRateId = null,
        public readonly ?float $taxRate = null,
        public readonly bool $isTaxInclusive = false,
        public readonly ?string $profitMarginType = null,
        public readonly ?float $profitMarginValue = null,
        public readonly ?array $context = [],
    ) {
        $this->validate();
    }

    /**
     * Validate DTO data
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be greater than 0');
        }

        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        if ($this->basePrice < 0) {
            throw new \InvalidArgumentException('Base price cannot be negative');
        }

        if ($this->discountType && !in_array($this->discountType, ['none', 'flat', 'percentage', 'tiered'])) {
            throw new \InvalidArgumentException('Invalid discount type');
        }

        if ($this->profitMarginType && !in_array($this->profitMarginType, ['flat', 'percentage'])) {
            throw new \InvalidArgumentException('Invalid profit margin type');
        }
    }

    /**
     * Calculate discounted price
     *
     * @return float
     */
    public function calculateDiscountedPrice(): float
    {
        $price = $this->basePrice;

        if (!$this->discountType || $this->discountType === 'none' || !$this->discountValue) {
            return $price;
        }

        switch ($this->discountType) {
            case 'flat':
                return max(0, $price - $this->discountValue);
            
            case 'percentage':
                return max(0, $price - ($price * $this->discountValue / 100));
            
            case 'tiered':
                return $this->calculateTieredDiscount($price);
            
            default:
                return $price;
        }
    }

    /**
     * Calculate tiered discount
     *
     * @param float $price
     * @return float
     */
    protected function calculateTieredDiscount(float $price): float
    {
        $tiers = $this->context['discount_tiers'] ?? [];
        
        if (empty($tiers)) {
            return $price;
        }

        usort($tiers, function ($a, $b) {
            return $b['min_quantity'] <=> $a['min_quantity'];
        });

        foreach ($tiers as $tier) {
            if ($this->quantity >= $tier['min_quantity']) {
                $discountValue = $tier['discount_value'] ?? 0;
                $discountType = $tier['discount_type'] ?? 'flat';

                if ($discountType === 'flat') {
                    return max(0, $price - $discountValue);
                } elseif ($discountType === 'percentage') {
                    return max(0, $price - ($price * $discountValue / 100));
                }
            }
        }

        return $price;
    }

    /**
     * Calculate tax amount
     *
     * @param float $discountedPrice
     * @return float
     */
    public function calculateTaxAmount(float $discountedPrice): float
    {
        if (!$this->taxRate || $this->taxRate <= 0) {
            return 0;
        }

        if ($this->isTaxInclusive) {
            return $discountedPrice - ($discountedPrice / (1 + ($this->taxRate / 100)));
        }

        return $discountedPrice * ($this->taxRate / 100);
    }

    /**
     * Calculate final price with all adjustments
     *
     * @return array
     */
    public function calculateFinalPrice(): array
    {
        $discountedPrice = $this->calculateDiscountedPrice();
        $taxAmount = $this->calculateTaxAmount($discountedPrice);
        
        $finalPrice = $this->isTaxInclusive 
            ? $discountedPrice 
            : $discountedPrice + $taxAmount;

        $totalAmount = $finalPrice * $this->quantity;
        $discountAmount = ($this->basePrice - $discountedPrice) * $this->quantity;

        $profitMargin = null;
        $profitMarginPercentage = null;
        
        if ($this->buyingPrice !== null && $this->buyingPrice > 0) {
            $profitMargin = $discountedPrice - $this->buyingPrice;
            $profitMarginPercentage = ($profitMargin / $this->buyingPrice) * 100;
        }

        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'base_price' => $this->basePrice,
            'base_amount' => $this->basePrice * $this->quantity,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'discount_amount' => $discountAmount,
            'discounted_price' => $discountedPrice,
            'discounted_amount' => $discountedPrice * $this->quantity,
            'tax_rate' => $this->taxRate,
            'tax_amount' => $taxAmount * $this->quantity,
            'is_tax_inclusive' => $this->isTaxInclusive,
            'final_price' => $finalPrice,
            'total_amount' => $totalAmount,
            'profit_margin' => $profitMargin,
            'profit_margin_percentage' => $profitMarginPercentage,
        ];
    }
}
