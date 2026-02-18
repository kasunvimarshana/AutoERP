<?php

declare(strict_types=1);

namespace Modules\Sales\ValueObjects;

use JsonSerializable;
use Modules\Core\ValueObjects\CurrencyAmount;
use Stringable;

/**
 * Sales Order Total Value Object
 *
 * Represents the total amount of a sales order including
 * subtotal, tax, discount, and shipping calculations.
 *
 * @package Modules\Sales\ValueObjects
 */
final class OrderTotal implements JsonSerializable, Stringable
{
    private readonly CurrencyAmount $subtotal;
    private readonly CurrencyAmount $taxAmount;
    private readonly CurrencyAmount $discountAmount;
    private readonly CurrencyAmount $shippingAmount;
    private readonly CurrencyAmount $totalAmount;

    private function __construct(
        CurrencyAmount $subtotal,
        CurrencyAmount $taxAmount,
        CurrencyAmount $discountAmount,
        CurrencyAmount $shippingAmount
    ) {
        // Ensure all amounts are in same currency
        $currency = $subtotal->getCurrency();
        
        if ($taxAmount->getCurrency() !== $currency ||
            $discountAmount->getCurrency() !== $currency ||
            $shippingAmount->getCurrency() !== $currency) {
            throw new \InvalidArgumentException('All amounts must be in the same currency');
        }

        $this->subtotal = $subtotal;
        $this->taxAmount = $taxAmount;
        $this->discountAmount = $discountAmount;
        $this->shippingAmount = $shippingAmount;

        // Calculate total: subtotal + tax - discount + shipping
        $this->totalAmount = $subtotal
            ->add($taxAmount)
            ->subtract($discountAmount)
            ->add($shippingAmount);
    }

    public static function calculate(
        CurrencyAmount $subtotal,
        ?CurrencyAmount $taxAmount = null,
        ?CurrencyAmount $discountAmount = null,
        ?CurrencyAmount $shippingAmount = null
    ): self {
        $currency = $subtotal->getCurrency();
        
        return new self(
            $subtotal,
            $taxAmount ?? CurrencyAmount::zero($currency),
            $discountAmount ?? CurrencyAmount::zero($currency),
            $shippingAmount ?? CurrencyAmount::zero($currency)
        );
    }

    public static function fromSubtotal(CurrencyAmount $subtotal): self
    {
        $currency = $subtotal->getCurrency();
        
        return new self(
            $subtotal,
            CurrencyAmount::zero($currency),
            CurrencyAmount::zero($currency),
            CurrencyAmount::zero($currency)
        );
    }

    public function withTax(CurrencyAmount $taxAmount): self
    {
        return new self(
            $this->subtotal,
            $taxAmount,
            $this->discountAmount,
            $this->shippingAmount
        );
    }

    public function withDiscount(CurrencyAmount $discountAmount): self
    {
        return new self(
            $this->subtotal,
            $this->taxAmount,
            $discountAmount,
            $this->shippingAmount
        );
    }

    public function withShipping(CurrencyAmount $shippingAmount): self
    {
        return new self(
            $this->subtotal,
            $this->taxAmount,
            $this->discountAmount,
            $shippingAmount
        );
    }

    public function getSubtotal(): CurrencyAmount
    {
        return $this->subtotal;
    }

    public function getTaxAmount(): CurrencyAmount
    {
        return $this->taxAmount;
    }

    public function getDiscountAmount(): CurrencyAmount
    {
        return $this->discountAmount;
    }

    public function getShippingAmount(): CurrencyAmount
    {
        return $this->shippingAmount;
    }

    public function getTotal(): CurrencyAmount
    {
        return $this->totalAmount;
    }

    public function getCurrency(): string
    {
        return $this->totalAmount->getCurrency();
    }

    public function getDiscountPercentage(): float
    {
        if ($this->subtotal->isZero()) {
            return 0.0;
        }

        $discountRatio = (float)$this->discountAmount->toMajorUnits() / 
                        (float)$this->subtotal->toMajorUnits();
        
        return round($discountRatio * 100, 2);
    }

    public function getTaxPercentage(): float
    {
        if ($this->subtotal->isZero()) {
            return 0.0;
        }

        $taxRatio = (float)$this->taxAmount->toMajorUnits() / 
                   (float)$this->subtotal->toMajorUnits();
        
        return round($taxRatio * 100, 2);
    }

    public function __toString(): string
    {
        return sprintf(
            'Subtotal: %s, Tax: %s, Discount: %s, Shipping: %s, Total: %s',
            $this->subtotal,
            $this->taxAmount,
            $this->discountAmount,
            $this->shippingAmount,
            $this->totalAmount
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'subtotal' => $this->subtotal->jsonSerialize(),
            'tax' => $this->taxAmount->jsonSerialize(),
            'discount' => $this->discountAmount->jsonSerialize(),
            'shipping' => $this->shippingAmount->jsonSerialize(),
            'total' => $this->totalAmount->jsonSerialize(),
            'currency' => $this->getCurrency(),
            'discountPercentage' => $this->getDiscountPercentage(),
            'taxPercentage' => $this->getTaxPercentage(),
        ];
    }
}
