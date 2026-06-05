<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Services;

use Illuminate\Validation\ValidationException;

final class InvoiceCalculationService
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array<string, float>
     */
    public function calculate(array $lines, array $adjustments = [], float $roundingAdjustment = 0): array
    {
        $gross = 0.0;
        $lineDiscount = 0.0;
        $taxable = 0.0;
        $tax = 0.0;
        $charges = 0.0;

        foreach (array_values($lines) as $index => $line) {
            $quantity = (float) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            if ($quantity <= 0 || $unitPrice < 0) {
                throw ValidationException::withMessages(["lines.$index.quantity" => ['Line quantity must be positive and unit price cannot be negative.']]);
            }
            $lineGross = round($quantity * $unitPrice, 4);
            $discount = $this->amount($line['discount_total'] ?? 0, "lines.$index.discount_total");
            $lineTax = $this->amount($line['tax_total'] ?? 0, "lines.$index.tax_total");
            $lineCharge = $this->amount($line['charge_total'] ?? 0, "lines.$index.charge_total");
            $gross += $lineGross;
            $lineDiscount += $discount;
            $taxable += max(0, $lineGross - $discount);
            $tax += $lineTax;
            $charges += $lineCharge;
        }

        $headerDiscount = 0.0;
        $debitAdjustments = 0.0;
        $creditAdjustments = 0.0;
        $writeOff = 0.0;
        foreach (array_values($adjustments) as $index => $adjustment) {
            $amount = $this->amount($adjustment['amount'] ?? 0, "adjustments.$index.amount");
            $effect = (string) ($adjustment['effect'] ?? '');
            $type = (string) ($adjustment['adjustment_type'] ?? 'adjustment');
            if (! in_array($effect, ['add', 'deduct', 'subtract'], true)) {
                throw ValidationException::withMessages(["adjustments.$index.effect" => ['Adjustment effect must be add or deduct.']]);
            }
            if ($type === 'discount') {
                $headerDiscount += $amount;
            } elseif ($type === 'write_off') {
                $writeOff += $amount;
            } elseif ($effect === 'add') {
                $debitAdjustments += $amount;
            } else {
                $creditAdjustments += $amount;
            }
        }

        $grand = max(0, $gross - $lineDiscount - $headerDiscount + $tax + $charges + $debitAdjustments - $creditAdjustments + $roundingAdjustment - $writeOff);

        return [
            'gross_total' => round($gross, 4),
            'line_discount_total' => round($lineDiscount, 4),
            'header_discount_total' => round($headerDiscount, 4),
            'taxable_total' => round($taxable, 4),
            'tax_total' => round($tax, 4),
            'charge_total' => round($charges, 4),
            'rounding_adjustment' => round($roundingAdjustment, 4),
            'debit_adjustment_total' => round($debitAdjustments, 4),
            'credit_adjustment_total' => round($creditAdjustments, 4),
            'write_off_total' => round($writeOff, 4),
            'refund_total' => 0.0,
            'grand_total' => round($grand, 4),
            'settled_total' => 0.0,
            'balance_total' => round($grand, 4),
        ];
    }

    private function amount(mixed $value, string $field): float
    {
        $amount = (float) $value;
        if ($amount < 0) {
            throw ValidationException::withMessages([$field => ['Amounts must be non-negative magnitudes.']]);
        }

        return $amount;
    }
}
