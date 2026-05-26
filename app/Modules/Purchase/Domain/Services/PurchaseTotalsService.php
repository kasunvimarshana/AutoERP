<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Modules\Purchase\Domain\Enums\DiscountType;

final class PurchaseTotalsService
{
    public function __construct(private readonly TaxCalculationService $taxes)
    {
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    public function calculateLine(array $line, string $quantityColumn): array
    {
        $quantity = (float) ($line[$quantityColumn] ?? 0);
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $gross = round($quantity * $unitPrice, 4);
        $discountType = $line['discount_type'] ?? null;
        $discountValue = (float) ($line['discount_value'] ?? 0);
        $discount = 0.0;

        if ($discountType === DiscountType::Percentage->value) {
            $discount = round($gross * ($discountValue / 100), 4);
        } elseif ($discountType === DiscountType::Fixed->value) {
            $discount = min($gross, $discountValue);
        }

        $lineTotal = round($gross - $discount, 4);
        $tax = $this->taxes->calculateLineTax($lineTotal, isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null);

        return array_merge($line, [
            'discount_amount' => round($discount, 4),
            'gross_amount' => $gross,
            'line_total' => $lineTotal,
            'tax_amount' => $tax,
            'line_total_with_tax' => round($lineTotal + $tax, 4),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $header
     * @return array<string, mixed>
     */
    public function calculateHeader(array $lines, array $header, bool $includeRestocking = false): array
    {
        $subtotal = (float) array_sum(array_map(static fn (array $line): float => (float) ($line['gross_amount'] ?? 0), $lines));
        $lineTaxTotal = (float) array_sum(array_map(static fn (array $line): float => (float) ($line['tax_amount'] ?? 0), $lines));
        $lineDiscountTotal = (float) array_sum(array_map(static fn (array $line): float => (float) ($line['discount_amount'] ?? 0), $lines));
        $lineRestockingTotal = $includeRestocking
            ? (float) array_sum(array_map(static fn (array $line): float => (float) ($line['restocking_fee'] ?? 0), $lines))
            : 0.0;

        $headerDiscountType = $header['header_discount_type'] ?? null;
        $headerDiscountValue = (float) ($header['header_discount_value'] ?? 0);
        $lineNet = (float) array_sum(array_map(static fn (array $line): float => (float) ($line['line_total'] ?? 0), $lines));
        $headerDiscount = 0.0;

        if ($headerDiscountType === DiscountType::Percentage->value) {
            $headerDiscount = round($lineNet * ($headerDiscountValue / 100), 4);
        } elseif ($headerDiscountType === DiscountType::Fixed->value) {
            $headerDiscount = min($lineNet, $headerDiscountValue);
        }

        $headerTax = $this->taxes->calculateLineTax(
            max(0.0, $lineNet - $headerDiscount),
            isset($header['header_tax_group_id']) ? (int) $header['header_tax_group_id'] : null,
        );

        $discountTotal = round($lineDiscountTotal + $headerDiscount, 4);
        $taxTotal = round($lineTaxTotal + $headerTax, 4);
        $grandTotal = round(
            $subtotal
            - $discountTotal
            + $taxTotal
            + (float) ($header['debit_note_total'] ?? 0)
            - (float) ($header['credit_note_total'] ?? 0)
            - $lineRestockingTotal,
            4,
        );

        $totals = [
            'subtotal' => round($subtotal, 4),
            'line_tax_total' => round($lineTaxTotal, 4),
            'line_discount_total' => round($lineDiscountTotal, 4),
            'header_discount_amount' => round($headerDiscount, 4),
            'header_tax_amount' => round($headerTax, 4),
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ];

        if ($includeRestocking) {
            $totals['line_restocking_total'] = round($lineRestockingTotal, 4);
        }

        return $totals;
    }
}
