<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

final class PurchaseCalculationService
{
    public function calculateLine(array $line, string $quantityField = 'ordered_qty'): array
    {
        $quantity = $this->money($line[$quantityField] ?? $line['quantity'] ?? 0);
        $unitPrice = $this->money($line['unit_price'] ?? 0);
        $grossAmount = $this->round($quantity * $unitPrice);
        $discountAmount = $this->discountAmount(
            $grossAmount,
            (string) ($line['discount_type'] ?? ''),
            $this->money($line['discount_value'] ?? 0),
        );
        $lineTotal = $this->round(max(0.0, $grossAmount - $discountAmount));
        $taxAmount = $this->money($line['tax_amount'] ?? 0);

        return array_merge($line, [
            'discount_amount' => $discountAmount,
            'gross_amount' => $grossAmount,
            'line_total' => $lineTotal,
            'tax_amount' => $taxAmount,
            'line_total_with_tax' => $this->round($lineTotal + $taxAmount),
        ]);
    }

    /**
     * @param iterable<array<string, mixed>|object> $lines
     * @return array<string, float>
     */
    public function calculateTotals(iterable $lines, array $header = [], bool $isReturn = false): array
    {
        $subtotal = 0.0;
        $lineTaxTotal = 0.0;
        $lineDiscountTotal = 0.0;
        $lineRestockingTotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += $this->recordMoney($line, 'gross_amount');
            $lineTaxTotal += $this->recordMoney($line, 'tax_amount');
            $lineDiscountTotal += $this->recordMoney($line, 'discount_amount');
            $lineRestockingTotal += $this->recordMoney($line, 'restocking_fee');
        }

        $headerDiscountAmount = $this->discountAmount(
            max(0.0, $subtotal - $lineDiscountTotal),
            (string) ($header['header_discount_type'] ?? ''),
            $this->money($header['header_discount_value'] ?? 0),
        );
        $headerTaxAmount = $this->money($header['header_tax_amount'] ?? 0);
        $discountTotal = $this->round($lineDiscountTotal + $headerDiscountAmount);
        $taxTotal = $this->round($lineTaxTotal + $headerTaxAmount);
        $chargeTotal = $isReturn ? $this->round($lineRestockingTotal) : $this->money($header['charge_total'] ?? 0);
        $grandTotal = $isReturn
            ? $this->round($subtotal - $discountTotal + $taxTotal - $chargeTotal)
            : $this->round($subtotal - $discountTotal + $taxTotal + $chargeTotal);
        $paidAmount = $this->money($header['paid_amount'] ?? 0);

        return [
            'subtotal' => $this->round($subtotal),
            'line_tax_total' => $this->round($lineTaxTotal),
            'line_discount_total' => $this->round($lineDiscountTotal),
            'line_restocking_total' => $this->round($lineRestockingTotal),
            'header_discount_amount' => $headerDiscountAmount,
            'header_tax_amount' => $headerTaxAmount,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'charge_total' => $chargeTotal,
            'grand_total' => $grandTotal,
            'paid_amount' => $paidAmount,
            'balance' => $this->round($grandTotal - $paidAmount),
        ];
    }

    public function discountAmount(float $baseAmount, string $type, float $value): float
    {
        if ($value <= 0 || $baseAmount <= 0) {
            return 0.0;
        }

        if (strtolower(trim($type)) === 'percentage') {
            return $this->round(min($baseAmount, $baseAmount * $value / 100));
        }

        return $this->round(min($baseAmount, $value));
    }

    private function recordMoney(array|object $record, string $key): float
    {
        if (is_array($record)) {
            return $this->money($record[$key] ?? 0);
        }

        return $this->money($record->{$key} ?? 0);
    }

    private function money(mixed $value): float
    {
        return (float) ($value ?? 0);
    }

    private function round(float $value): float
    {
        return round($value, 4);
    }
}
