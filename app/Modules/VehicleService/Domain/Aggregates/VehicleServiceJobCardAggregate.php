<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Aggregates;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VehicleServiceJobCardAggregate
{
    public function __construct(
        private readonly Collection $partsLines,
        private readonly Collection $nonInventoryItems,
        private readonly Collection $laborItems,
        private readonly array $attributes,
        private readonly int $scale,
        private readonly string $headerDiscountBase,
    ) {}

    /**
     * @return array<string, string>
     */
    public function totals(): array
    {
        $parts = $this->bucketTotals($this->partsLines);
        $nonInventory = $this->bucketTotals($this->nonInventoryItems);
        $labor = $this->bucketTotals($this->laborItems);
        $allSubtotal = $parts['subtotal'] + $nonInventory['subtotal'] + $labor['subtotal'];
        $allLineDiscount = $parts['discount'] + $nonInventory['discount'] + $labor['discount'];
        $allLineTax = $parts['tax'] + $nonInventory['tax'] + $labor['tax'];
        $headerDiscountAmount = $this->discountAmount(
            (string) ($this->attributes['header_discount_type'] ?? 'fixed'),
            $this->attributes['header_discount_value'] ?? ($this->attributes['header_discount_amount'] ?? 0),
            $this->headerDiscountBase($allSubtotal, $allLineDiscount, $allLineTax),
        );

        $totals = [
            'subtotal' => $this->decimal($parts['subtotal']),
            'line_discount_total' => $this->decimal($parts['discount']),
            'line_tax_total' => $this->decimal($parts['tax']),
            'non_inventory_item_subtotal' => $this->decimal($nonInventory['subtotal']),
            'non_inventory_item_discount_total' => $this->decimal($nonInventory['discount']),
            'non_inventory_item_tax_total' => $this->decimal($nonInventory['tax']),
            'labor_item_subtotal' => $this->decimal($labor['subtotal']),
            'labor_item_discount_total' => $this->decimal($labor['discount']),
            'labor_item_tax_total' => $this->decimal($labor['tax']),
            'header_discount_amount' => $this->decimal($headerDiscountAmount),
            'header_tax_amount' => $this->decimal($this->attributes['header_tax_amount'] ?? 0),
            'debit_note_total' => $this->decimal($this->attributes['debit_note_total'] ?? 0),
            'credit_note_total' => $this->decimal($this->attributes['credit_note_total'] ?? 0),
            'paid_amount' => $this->decimal($this->attributes['paid_amount'] ?? 0),
        ];

        $totals['discount_total'] = $this->decimal($allLineDiscount + (float) $totals['header_discount_amount']);
        $totals['tax_total'] = $this->decimal($allLineTax + (float) $totals['header_tax_amount']);
        $totals['grand_total'] = $this->decimal(
            $allSubtotal
            - (float) $totals['discount_total']
            + (float) $totals['tax_total']
            + (float) $totals['debit_note_total']
            - (float) $totals['credit_note_total'],
        );
        $totals['balance'] = $this->decimal((float) $totals['grand_total'] - (float) $totals['paid_amount']);

        return $totals;
    }

    /**
     * @return array{subtotal: float, discount: float, tax: float}
     */
    private function bucketTotals(Collection $lines): array
    {
        $totals = ['subtotal' => 0.0, 'discount' => 0.0, 'tax' => 0.0];

        foreach ($lines as $line) {
            $totals['subtotal'] += $this->lineGross($line);
            $totals['discount'] += (float) ($line->discount_amount ?? 0);
            $totals['tax'] += (float) ($line->tax_amount ?? 0);
        }

        return $totals;
    }

    private function lineGross(Model $line): float
    {
        return (float) ($line->gross_amount ?? ((float) ($line->quantity ?? 0) * (float) ($line->unit_price ?? 0)));
    }

    private function headerDiscountBase(float $subtotal, float $lineDiscountTotal, float $lineTaxTotal): float
    {
        return match ($this->headerDiscountBase) {
            'gross' => $subtotal,
            'with_line_tax' => $subtotal - $lineDiscountTotal + $lineTaxTotal,
            default => $subtotal - $lineDiscountTotal,
        };
    }

    private function discountAmount(string $type, mixed $value, float $base): float
    {
        $amount = $type === 'percentage'
            ? $base * ((float) ($value ?? 0) / 100)
            : (float) ($value ?? 0);

        return min(max($amount, 0.0), max($base, 0.0));
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) ($value ?? 0), $this->scale, '.', '');
    }
}
