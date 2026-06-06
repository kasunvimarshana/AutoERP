<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\PurchaseCalculationResult;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
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

    /**
     * @return array{discount: string, tax: string, charge: string, subtotal: string, total: string}
     */
    public function lineAmounts(object $line, string $quantityProperty = 'orderedQuantity'): array
    {
        $quantity = (string) ($line->{$quantityProperty} ?? $line->orderedQuantity ?? $line->acceptedQuantity ?? $line->returnedQuantity);
        $subtotal = $this->lineBase($quantity, (string) $line->unitPrice);
        $discount = $this->calculatedAmount(
            $subtotal,
            $line->discountCalculationType ?? PurchaseAdjustmentCalculationType::Fixed,
            (string) ($line->discountRate ?? '0.000000'),
            (string) ($line->discountAmount ?? '0.000000'),
        );
        $taxBase = $this->math->sub($subtotal, $discount);
        $tax = $this->calculatedAmount(
            $taxBase,
            $line->taxCalculationType ?? PurchaseAdjustmentCalculationType::Fixed,
            (string) ($line->taxRate ?? '0.000000'),
            (string) ($line->taxAmount ?? '0.000000'),
        );
        $charge = $this->calculatedAmount(
            $subtotal,
            $line->chargeCalculationType ?? PurchaseAdjustmentCalculationType::Fixed,
            (string) ($line->chargeRate ?? '0.000000'),
            (string) ($line->chargeAmount ?? '0.000000'),
        );

        return [
            'discount' => $discount,
            'tax' => $tax,
            'charge' => $charge,
            'subtotal' => $subtotal,
            'total' => $this->lineTotal($quantity, (string) $line->unitPrice, $discount, $tax, $charge),
        ];
    }

    public function calculate(array $lineData, array $adjustments = []): PurchaseCalculationResult
    {
        $subtotal = '0.000000';
        $discountTotal = '0.000000';
        $taxTotal = '0.000000';
        $chargeTotal = '0.000000';
        $adjustmentTotal = '0.000000';
        $headerIncreaseTotal = '0.000000';
        $headerDecreaseTotal = '0.000000';
        $lineTotals = [];
        $subtotalAfterLineDiscount = '0.000000';
        $subtotalAfterLineAdjustments = '0.000000';

        foreach ($lineData as $line) {
            $amounts = $this->lineAmounts($line);
            $base = $amounts['subtotal'];
            $lineTotals[] = $amounts['total'];
            if ($this->math->isNegative($lineTotals[array_key_last($lineTotals)])) {
                throw new \InvalidArgumentException('Purchase line total cannot be negative.');
            }
            $subtotal = $this->math->add($subtotal, $base);
            $discountTotal = $this->math->add($discountTotal, $amounts['discount']);
            $taxTotal = $this->math->add($taxTotal, $amounts['tax']);
            $chargeTotal = $this->math->add($chargeTotal, $amounts['charge']);
            $subtotalAfterLineDiscount = $this->math->add($subtotalAfterLineDiscount, $this->math->sub($base, $amounts['discount']));
            $subtotalAfterLineAdjustments = $this->math->add($subtotalAfterLineAdjustments, $amounts['total']);
        }

        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustmentData) {
                continue;
            }

            $amount = $this->headerAdjustmentAmount(
                $adjustment,
                $subtotal,
                $subtotalAfterLineDiscount,
                $subtotalAfterLineAdjustments,
            );
            if ($adjustment->adjustmentType === PurchaseAdjustmentType::Discount
                || $adjustment->adjustmentType === PurchaseAdjustmentType::CreditNote
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Withholding) {
                $discountTotal = $this->math->add($discountTotal, $amount);
            } elseif ($adjustment->adjustmentType === PurchaseAdjustmentType::Tax) {
                $taxTotal = $this->math->add($taxTotal, $amount);
            } elseif ($adjustment->adjustmentType === PurchaseAdjustmentType::Freight
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Charge
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Insurance
                || $adjustment->adjustmentType === PurchaseAdjustmentType::ServiceCharge
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Duty
                || $adjustment->adjustmentType === PurchaseAdjustmentType::Levy
                || $adjustment->adjustmentType === PurchaseAdjustmentType::DebitNote) {
                $chargeTotal = $this->math->add($chargeTotal, $amount);
            }

            if ($adjustment->effect === PurchaseAdjustmentEffect::Increase) {
                $adjustmentTotal = $this->math->add($adjustmentTotal, $amount);
                $headerIncreaseTotal = $this->math->add($headerIncreaseTotal, $amount);
            } else {
                $adjustmentTotal = $this->math->sub($adjustmentTotal, $amount);
                $headerDecreaseTotal = $this->math->add($headerDecreaseTotal, $amount);
            }
        }

        $grandTotal = $this->math->sum($lineTotals);
        $grandTotal = $this->math->add($grandTotal, $adjustmentTotal);
        if ($this->math->isNegative($grandTotal)) {
            throw new \InvalidArgumentException('Purchase order total cannot be negative.');
        }

        return new PurchaseCalculationResult(
            $subtotal,
            $discountTotal,
            $taxTotal,
            $chargeTotal,
            $adjustmentTotal,
            $grandTotal,
            $lineTotals,
            $headerIncreaseTotal,
            $headerDecreaseTotal,
        );
    }

    public function calculatedAmount(
        string $base,
        PurchaseAdjustmentCalculationType|string $calculationType,
        string $rate,
        string $fixedAmount,
    ): string {
        $type = $calculationType instanceof PurchaseAdjustmentCalculationType
            ? $calculationType
            : PurchaseAdjustmentCalculationType::from($calculationType);

        if ($type === PurchaseAdjustmentCalculationType::Percentage) {
            return $this->math->div($this->math->mul($base, $rate, 12), '100');
        }

        return $this->math->normalize($fixedAmount);
    }

    public function headerAdjustmentAmount(
        PurchaseHeaderAdjustmentData $adjustment,
        string $subtotal,
        string $subtotalAfterLineDiscount,
        string $subtotalAfterLineAdjustments,
    ): string {
        $base = match ($adjustment->calculationBase) {
            PurchaseAdjustmentCalculationBase::Subtotal => $subtotal,
            PurchaseAdjustmentCalculationBase::SubtotalAfterLineDiscount => $subtotalAfterLineDiscount,
            PurchaseAdjustmentCalculationBase::SubtotalAfterLineAdjustments => $subtotalAfterLineAdjustments,
        };

        return $this->calculatedAmount($base, $adjustment->calculationType, $adjustment->rate, $adjustment->amount);
    }

    /**
     * @param  list<object>  $lineData
     * @param  list<PurchaseHeaderAdjustmentData>  $adjustments
     * @return list<string>
     */
    public function headerAdjustmentAmounts(array $lineData, array $adjustments): array
    {
        $subtotal = '0.000000';
        $subtotalAfterLineDiscount = '0.000000';
        $subtotalAfterLineAdjustments = '0.000000';

        foreach ($lineData as $line) {
            $amounts = $this->lineAmounts($line);
            $subtotal = $this->math->add($subtotal, $amounts['subtotal']);
            $subtotalAfterLineDiscount = $this->math->add($subtotalAfterLineDiscount, $this->math->sub($amounts['subtotal'], $amounts['discount']));
            $subtotalAfterLineAdjustments = $this->math->add($subtotalAfterLineAdjustments, $amounts['total']);
        }

        return array_map(
            fn (PurchaseHeaderAdjustmentData $adjustment): string => $this->headerAdjustmentAmount(
                $adjustment,
                $subtotal,
                $subtotalAfterLineDiscount,
                $subtotalAfterLineAdjustments,
            ),
            $adjustments,
        );
    }
}
