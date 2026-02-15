<?php

declare(strict_types=1);

namespace App\Modules\Product\DTOs;

use App\Core\DTOs\BaseDTO;

/**
 * Pricing Calculation Data Transfer Object
 * 
 * Handles complex pricing calculations with discounts, taxes, and adjustments
 */
class PricingDTO extends BaseDTO
{
    public function __construct(
        public readonly float $base_price,
        public readonly float $quantity = 1.0,
        public readonly ?float $item_discount_flat = null,
        public readonly ?float $item_discount_percentage = null,
        public readonly ?float $total_discount_flat = null,
        public readonly ?float $total_discount_percentage = null,
        public readonly ?float $vat_rate = null,
        public readonly bool $vat_inclusive = false,
        public readonly ?float $tax_rate = null,
        public readonly bool $tax_inclusive = false,
        public readonly ?float $coupon_discount = null,
        public readonly ?float $additional_charges = null,
        public readonly ?int $price_list_id = null,
        public readonly ?string $customer_tier = null,
        public readonly ?array $tiered_discounts = null, // ['qty' => 10, 'discount' => 5]
        public readonly ?array $seasonal_adjustments = null,
        public readonly ?array $metadata = null,
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
        if ($this->base_price < 0) {
            throw new \InvalidArgumentException('Base price cannot be negative');
        }

        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        if ($this->item_discount_percentage !== null && ($this->item_discount_percentage < 0 || $this->item_discount_percentage > 100)) {
            throw new \InvalidArgumentException('Item discount percentage must be between 0 and 100');
        }

        if ($this->total_discount_percentage !== null && ($this->total_discount_percentage < 0 || $this->total_discount_percentage > 100)) {
            throw new \InvalidArgumentException('Total discount percentage must be between 0 and 100');
        }

        if ($this->vat_rate !== null && $this->vat_rate < 0) {
            throw new \InvalidArgumentException('VAT rate cannot be negative');
        }

        if ($this->tax_rate !== null && $this->tax_rate < 0) {
            throw new \InvalidArgumentException('Tax rate cannot be negative');
        }
    }

    /**
     * Calculate price after item-level discounts
     *
     * @return float
     */
    public function getPriceAfterItemDiscounts(): float
    {
        $price = $this->base_price;

        // Apply tiered discounts based on quantity
        if ($this->tiered_discounts !== null) {
            $tierDiscount = $this->getTieredDiscount();
            $price -= $tierDiscount;
        }

        // Apply flat item discount
        if ($this->item_discount_flat !== null) {
            $price -= $this->item_discount_flat;
        }

        // Apply percentage item discount
        if ($this->item_discount_percentage !== null) {
            $price -= ($price * $this->item_discount_percentage / 100);
        }

        // Apply seasonal adjustments
        if ($this->seasonal_adjustments !== null) {
            $price = $this->applySeasonalAdjustments($price);
        }

        return max(0, $price);
    }

    /**
     * Calculate subtotal (price * quantity)
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->getPriceAfterItemDiscounts() * $this->quantity;
    }

    /**
     * Calculate price after total-level discounts
     *
     * @return float
     */
    public function getTotalAfterDiscounts(): float
    {
        $total = $this->getSubtotal();

        // Apply flat total discount
        if ($this->total_discount_flat !== null) {
            $total -= $this->total_discount_flat;
        }

        // Apply percentage total discount
        if ($this->total_discount_percentage !== null) {
            $total -= ($total * $this->total_discount_percentage / 100);
        }

        // Apply coupon discount
        if ($this->coupon_discount !== null) {
            $total -= $this->coupon_discount;
        }

        return max(0, $total);
    }

    /**
     * Calculate VAT amount
     *
     * @return float
     */
    public function getVATAmount(): float
    {
        if ($this->vat_rate === null || $this->vat_rate == 0) {
            return 0;
        }

        $base = $this->getTotalAfterDiscounts();

        if ($this->vat_inclusive) {
            // VAT is included in the price, extract it
            return $base - ($base / (1 + ($this->vat_rate / 100)));
        }

        // VAT is additional
        return $base * ($this->vat_rate / 100);
    }

    /**
     * Calculate tax amount
     *
     * @return float
     */
    public function getTaxAmount(): float
    {
        if ($this->tax_rate === null || $this->tax_rate == 0) {
            return 0;
        }

        $base = $this->getTotalAfterDiscounts();

        if ($this->tax_inclusive) {
            // Tax is included in the price, extract it
            return $base - ($base / (1 + ($this->tax_rate / 100)));
        }

        // Tax is additional
        return $base * ($this->tax_rate / 100);
    }

    /**
     * Calculate final total including VAT, tax, and additional charges
     *
     * @return float
     */
    public function getFinalTotal(): float
    {
        $total = $this->getTotalAfterDiscounts();

        // Add VAT if not inclusive
        if (!$this->vat_inclusive) {
            $total += $this->getVATAmount();
        }

        // Add tax if not inclusive
        if (!$this->tax_inclusive) {
            $total += $this->getTaxAmount();
        }

        // Add additional charges
        if ($this->additional_charges !== null) {
            $total += $this->additional_charges;
        }

        return max(0, $total);
    }

    /**
     * Get tiered discount based on quantity
     *
     * @return float
     */
    private function getTieredDiscount(): float
    {
        if ($this->tiered_discounts === null || empty($this->tiered_discounts)) {
            return 0;
        }

        $discount = 0;
        foreach ($this->tiered_discounts as $tier) {
            if ($this->quantity >= $tier['qty']) {
                $discount = $tier['discount'] ?? 0;
            }
        }

        return $discount;
    }

    /**
     * Apply seasonal adjustments to price
     *
     * @param float $price
     * @return float
     */
    private function applySeasonalAdjustments(float $price): float
    {
        if ($this->seasonal_adjustments === null || empty($this->seasonal_adjustments)) {
            return $price;
        }

        foreach ($this->seasonal_adjustments as $adjustment) {
            if ($this->isSeasonActive($adjustment)) {
                $price = $this->applyAdjustment($price, $adjustment);
            }
        }

        return $price;
    }

    /**
     * Check if seasonal adjustment is currently active
     *
     * @param array $adjustment
     * @return bool
     */
    private function isSeasonActive(array $adjustment): bool
    {
        $now = now();
        $start = isset($adjustment['start_date']) ? \Carbon\Carbon::parse($adjustment['start_date']) : null;
        $end = isset($adjustment['end_date']) ? \Carbon\Carbon::parse($adjustment['end_date']) : null;

        if ($start && $now->lt($start)) {
            return false;
        }

        if ($end && $now->gt($end)) {
            return false;
        }

        return true;
    }

    /**
     * Apply adjustment to price
     *
     * @param float $price
     * @param array $adjustment
     * @return float
     */
    private function applyAdjustment(float $price, array $adjustment): float
    {
        if (isset($adjustment['discount_percentage'])) {
            return $price - ($price * $adjustment['discount_percentage'] / 100);
        }

        if (isset($adjustment['discount_flat'])) {
            return $price - $adjustment['discount_flat'];
        }

        if (isset($adjustment['markup_percentage'])) {
            return $price + ($price * $adjustment['markup_percentage'] / 100);
        }

        return $price;
    }

    /**
     * Get pricing breakdown
     *
     * @return array
     */
    public function getBreakdown(): array
    {
        return [
            'base_price' => $this->base_price,
            'quantity' => $this->quantity,
            'price_after_item_discounts' => $this->getPriceAfterItemDiscounts(),
            'subtotal' => $this->getSubtotal(),
            'total_after_discounts' => $this->getTotalAfterDiscounts(),
            'vat_amount' => $this->getVATAmount(),
            'tax_amount' => $this->getTaxAmount(),
            'additional_charges' => $this->additional_charges ?? 0,
            'final_total' => $this->getFinalTotal(),
        ];
    }
}
