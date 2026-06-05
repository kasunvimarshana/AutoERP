<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Domain\Enums\InvoiceAdjustmentDirection;
use Modules\Invoice\Domain\Enums\InvoiceAdjustmentType;
use Modules\Invoice\Domain\Enums\InvoiceCalculationMethod;

final class InvoiceCalculationService
{
    public function calculateLineSubtotal(float|int|string $quantity, float|int|string $unitPrice): float
    {
        return $this->money($quantity) * $this->money($unitPrice);
    }

    /**
     * @param iterable<array<string, mixed>|Model> $adjustments
     * @return array{subtotal: float, discount_total: float, tax_total: float, charge_total: float, line_total: float}
     */
    public function calculateLine(array|Model $line, iterable $adjustments = []): array
    {
        $subtotal = $this->round($this->calculateLineSubtotal(
            $this->value($line, 'quantity', 1),
            $this->value($line, 'unit_price', 0),
        ));

        $summary = $this->summarizeAdjustments($adjustments, $subtotal);

        return [
            'subtotal' => $subtotal,
            'discount_total' => $summary['discount_total'],
            'tax_total' => $summary['tax_total'],
            'charge_total' => $summary['charge_total'],
            'line_total' => $this->round($subtotal + $summary['net_effect']),
        ];
    }

    /**
     * @param iterable<array<string, mixed>|Model> $lines
     * @param iterable<array<string, mixed>|Model> $headerAdjustments
     * @return array{subtotal: float, discount_total: float, tax_total: float, charge_total: float, adjustment_total: float, grand_total: float, paid_amount: float, balance_amount: float}
     */
    public function calculateInvoice(iterable $lines, iterable $headerAdjustments = [], float|int|string $paidAmount = 0): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;
        $chargeTotal = 0.0;
        $linesTotal = 0.0;

        foreach ($lines as $line) {
            $lineSubtotal = $this->money($this->value($line, 'subtotal', 0));
            $lineDiscount = $this->money($this->value($line, 'discount_total', 0));
            $lineTax = $this->money($this->value($line, 'tax_total', 0));
            $lineCharge = $this->money($this->value($line, 'charge_total', 0));

            $subtotal += $lineSubtotal;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;
            $chargeTotal += $lineCharge;
            $linesTotal += $this->money($this->value(
                $line,
                'line_total',
                $lineSubtotal - $lineDiscount + $lineTax + $lineCharge,
            ));
        }

        $headerSummary = $this->summarizeAdjustments($headerAdjustments, $subtotal);
        $grandTotal = $this->round($linesTotal + $headerSummary['net_effect']);
        $paid = $this->round($this->money($paidAmount));

        return [
            'subtotal' => $this->round($subtotal),
            'discount_total' => $this->round($discountTotal + $headerSummary['discount_total']),
            'tax_total' => $this->round($taxTotal + $headerSummary['tax_total']),
            'charge_total' => $this->round($chargeTotal + $headerSummary['charge_total']),
            'adjustment_total' => $headerSummary['other_total'],
            'grand_total' => $grandTotal,
            'paid_amount' => $paid,
            'balance_amount' => $this->round($grandTotal - $paid),
        ];
    }

    /**
     * @param iterable<array<string, mixed>|Model> $adjustments
     * @return array{discount_total: float, tax_total: float, charge_total: float, other_total: float, net_effect: float}
     */
    public function summarizeAdjustments(iterable $adjustments, float|int|string $defaultBaseAmount = 0): array
    {
        $discountTotal = 0.0;
        $taxTotal = 0.0;
        $chargeTotal = 0.0;
        $otherTotal = 0.0;
        $netEffect = 0.0;

        foreach ($adjustments as $adjustment) {
            $amount = $this->adjustmentAmount($adjustment, $defaultBaseAmount);
            $direction = $this->enumValue($this->value($adjustment, 'direction', InvoiceAdjustmentDirection::Add->value));
            $type = $this->enumValue($this->value($adjustment, 'adjustment_type', InvoiceAdjustmentType::Other->value));
            $signedAmount = $direction === InvoiceAdjustmentDirection::Subtract->value ? -$amount : $amount;

            $netEffect += $signedAmount;

            if ($type === InvoiceAdjustmentType::Discount->value) {
                $discountTotal += $amount;
            } elseif ($type === InvoiceAdjustmentType::Tax->value) {
                $taxTotal += $amount;
            } elseif ($type === InvoiceAdjustmentType::Charge->value) {
                $chargeTotal += $amount;
            } else {
                $otherTotal += $signedAmount;
            }
        }

        return [
            'discount_total' => $this->round($discountTotal),
            'tax_total' => $this->round($taxTotal),
            'charge_total' => $this->round($chargeTotal),
            'other_total' => $this->round($otherTotal),
            'net_effect' => $this->round($netEffect),
        ];
    }

    public function adjustmentAmount(array|Model $adjustment, float|int|string $defaultBaseAmount = 0): float
    {
        $method = $this->enumValue($this->value($adjustment, 'calculation_method', InvoiceCalculationMethod::Fixed->value));

        if ($method === InvoiceCalculationMethod::Percentage->value) {
            $baseAmount = $this->money($this->value($adjustment, 'base_amount', $defaultBaseAmount));
            $rate = $this->money($this->value($adjustment, 'rate', 0));

            return $this->round($baseAmount * $rate / 100);
        }

        return $this->round($this->money($this->value($adjustment, 'amount', 0)));
    }

    private function value(array|Model $record, string $key, mixed $default = null): mixed
    {
        if ($record instanceof Model) {
            return $record->getAttribute($key) ?? $default;
        }

        return $record[$key] ?? $default;
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private function money(float|int|string|null $value): float
    {
        return (float) ($value ?? 0);
    }

    private function round(float $value): float
    {
        return round($value, 4);
    }
}
