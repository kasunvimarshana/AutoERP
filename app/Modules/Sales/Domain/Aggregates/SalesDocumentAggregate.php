<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Aggregates;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SalesDocumentAggregate
{
    public function __construct(
        private readonly Collection $lines,
        private readonly array $attributes,
        private readonly int $scale,
        private readonly string $headerDiscountBase,
    ) {}

    /**
     * @return array<string, string>
     */
    public function totals(bool $includeRestocking = false, bool $includeBalance = false): array
    {
        $subtotal = 0.0;
        $lineDiscountTotal = 0.0;
        $lineTaxTotal = 0.0;
        $lineRestockingTotal = 0.0;

        foreach ($this->lines as $line) {
            $subtotal += $this->lineGross($line);
            $lineDiscountTotal += (float) ($line->discount_amount ?? 0);
            $lineTaxTotal += (float) ($line->tax_amount ?? 0);
            $lineRestockingTotal += (float) ($line->restocking_fee ?? 0);
        }

        $headerDiscountAmount = $this->discountAmount(
            (string) ($this->attributes['header_discount_type'] ?? 'fixed'),
            $this->attributes['header_discount_value'] ?? ($this->attributes['header_discount_amount'] ?? 0),
            $this->headerDiscountBase($subtotal, $lineDiscountTotal, $lineTaxTotal),
        );

        $totals = [
            'subtotal' => $this->decimal($subtotal),
            'line_discount_total' => $this->decimal($lineDiscountTotal),
            'line_tax_total' => $this->decimal($lineTaxTotal),
            'header_discount_amount' => $this->decimal($headerDiscountAmount),
            'header_tax_amount' => $this->decimal($this->attributes['header_tax_amount'] ?? 0),
            'debit_note_total' => $this->decimal($this->attributes['debit_note_total'] ?? 0),
            'credit_note_total' => $this->decimal($this->attributes['credit_note_total'] ?? 0),
        ];

        if ($includeRestocking) {
            $totals['line_restocking_total'] = $this->decimal($lineRestockingTotal);
        }

        $totals['discount_total'] = $this->decimal(
            (float) $totals['line_discount_total'] + (float) $totals['header_discount_amount'],
        );
        $totals['tax_total'] = $this->decimal(
            (float) $totals['line_tax_total'] + (float) $totals['header_tax_amount'],
        );
        $totals['grand_total'] = $this->decimal($this->grandTotal($totals, $includeRestocking));

        if ($includeBalance) {
            $totals['paid_amount'] = $this->decimal($this->attributes['paid_amount'] ?? 0);
            $totals['balance'] = $this->decimal((float) $totals['grand_total'] - (float) $totals['paid_amount']);
        }

        return $totals;
    }

    public function lineGross(Model $line): float
    {
        return (float) ($line->gross_amount ?? ((float) ($line->unit_price ?? 0) * $this->lineQuantity($line)));
    }

    private function lineQuantity(Model $line): float
    {
        foreach (['ordered_qty', 'delivered_qty', 'return_qty'] as $column) {
            if ($line->{$column} !== null) {
                return (float) $line->{$column};
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $totals
     */
    private function grandTotal(array $totals, bool $includeRestocking): float
    {
        $grandTotal = (float) ($totals['subtotal'] ?? 0)
            - (float) ($totals['line_discount_total'] ?? 0)
            - (float) ($totals['header_discount_amount'] ?? 0)
            + (float) ($totals['line_tax_total'] ?? 0)
            + (float) ($totals['header_tax_amount'] ?? 0)
            + (float) ($totals['debit_note_total'] ?? 0)
            - (float) ($totals['credit_note_total'] ?? 0);

        if ($includeRestocking) {
            $grandTotal -= (float) ($totals['line_restocking_total'] ?? 0);
        }

        return $grandTotal;
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
