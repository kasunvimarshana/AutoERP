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
        $lines = $this->aggregateLines($lineData);
        $subtotal = $lines['subtotal'];
        $discountTotal = $lines['discount_total'];
        $taxTotal = $lines['tax_total'];
        $chargeTotal = $lines['charge_total'];
        $adjustmentTotal = '0.000000';
        $headerIncreaseTotal = '0.000000';
        $headerDecreaseTotal = '0.000000';

        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustmentData) {
                continue;
            }

            $amount = $this->headerAdjustmentAmount(
                $adjustment,
                $subtotal,
                $lines['subtotal_after_discount'],
                $lines['subtotal_after_adjustments'],
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

        $grandTotal = $this->math->sum($lines['line_totals']);
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
            $lines['line_totals'],
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
        $lines = $this->aggregateLines($lineData);

        return array_map(
            fn (PurchaseHeaderAdjustmentData $adjustment): string => $this->headerAdjustmentAmount(
                $adjustment,
                $lines['subtotal'],
                $lines['subtotal_after_discount'],
                $lines['subtotal_after_adjustments'],
            ),
            $adjustments,
        );
    }

    /**
     * @param  list<object>  $lineData
     * @return array{
     *     subtotal: string,
     *     discount_total: string,
     *     tax_total: string,
     *     charge_total: string,
     *     subtotal_after_discount: string,
     *     subtotal_after_adjustments: string,
     *     line_totals: list<string>
     * }
     */
    private function aggregateLines(array $lineData): array
    {
        $totals = [
            'subtotal' => '0.000000',
            'discount_total' => '0.000000',
            'tax_total' => '0.000000',
            'charge_total' => '0.000000',
            'subtotal_after_discount' => '0.000000',
            'subtotal_after_adjustments' => '0.000000',
            'line_totals' => [],
        ];

        foreach ($lineData as $line) {
            $amounts = $this->lineAmounts($line);
            if ($this->math->isNegative($amounts['total'])) {
                throw new \InvalidArgumentException('Purchase line total cannot be negative.');
            }

            $totals['line_totals'][] = $amounts['total'];
            $totals['subtotal'] = $this->math->add($totals['subtotal'], $amounts['subtotal']);
            $totals['discount_total'] = $this->math->add($totals['discount_total'], $amounts['discount']);
            $totals['tax_total'] = $this->math->add($totals['tax_total'], $amounts['tax']);
            $totals['charge_total'] = $this->math->add($totals['charge_total'], $amounts['charge']);
            $totals['subtotal_after_discount'] = $this->math->add(
                $totals['subtotal_after_discount'],
                $this->math->sub($amounts['subtotal'], $amounts['discount']),
            );
            $totals['subtotal_after_adjustments'] = $this->math->add(
                $totals['subtotal_after_adjustments'],
                $amounts['total'],
            );
        }

        return $totals;
    }
}
