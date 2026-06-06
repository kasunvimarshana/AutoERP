<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\PurchaseCalculationResult;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

final class PurchaseOrderCalculationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function lineBase(string $quantity, string $unitPrice): string
    {
        return $this->math->mul($quantity, $unitPrice);
    }

    public function lineTotal(string $quantity, string $unitPrice, string $discount = '0.000000', string $tax = '0.000000', string $charge = '0.000000'): string
    {
        $total = $this->lineBase($quantity, $unitPrice);
        $total = $this->math->sub($total, $discount);
        $total = $this->math->add($total, $tax);

        return $this->math->add($total, $charge);
    }

    public function calculate(array $lineData, array $adjustments = []): PurchaseCalculationResult
    {
        $subtotal = '0.000000';
        $discountTotal = '0.000000';
        $taxTotal = '0.000000';
        $chargeTotal = '0.000000';
        $adjustmentTotal = '0.000000';
        $lineTotals = [];

        foreach ($lineData as $line) {
            $base = $this->lineBase($line->orderedQuantity ?? $line->acceptedQuantity ?? $line->returnedQuantity, $line->unitPrice);
            $lineTotals[] = $this->lineTotal(
                $line->orderedQuantity ?? $line->acceptedQuantity ?? $line->returnedQuantity,
                $line->unitPrice,
                $line->discountAmount,
                $line->taxAmount,
                $line->chargeAmount,
            );
            if ($this->math->isNegative($lineTotals[array_key_last($lineTotals)])) {
                throw new \InvalidArgumentException('Purchase line total cannot be negative.');
            }
            $subtotal = $this->math->add($subtotal, $base);
            $discountTotal = $this->math->add($discountTotal, $line->discountAmount);
            $taxTotal = $this->math->add($taxTotal, $line->taxAmount);
            $chargeTotal = $this->math->add($chargeTotal, $line->chargeAmount);
        }

        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustmentData) {
                continue;
            }

            $amount = $this->math->normalize($adjustment->amount);
            if ($adjustment->adjustmentType === PurchaseAdjustmentType::Discount
                || $adjustment->adjustmentType === PurchaseAdjustmentType::CreditNote
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Withholding) {
                $discountTotal = $this->math->add($discountTotal, $amount);
            } elseif ($adjustment->adjustmentType === PurchaseAdjustmentType::Tax) {
                $taxTotal = $this->math->add($taxTotal, $amount);
            } elseif ($adjustment->adjustmentType === PurchaseAdjustmentType::Freight
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Charge
                || $adjustment->adjustmentType === PurchaseAdjustmentType::DebitNote) {
                $chargeTotal = $this->math->add($chargeTotal, $amount);
            }

            $adjustmentTotal = $adjustment->effect === PurchaseAdjustmentEffect::Increase
                ? $this->math->add($adjustmentTotal, $amount)
                : $this->math->sub($adjustmentTotal, $amount);
        }

        $grandTotal = $this->math->sum($lineTotals);
        $grandTotal = $this->math->add($grandTotal, $adjustmentTotal);
        if ($this->math->isNegative($grandTotal)) {
            throw new \InvalidArgumentException('Purchase order total cannot be negative.');
        }

        return new PurchaseCalculationResult($subtotal, $discountTotal, $taxTotal, $chargeTotal, $adjustmentTotal, $grandTotal, $lineTotals);
    }
}
