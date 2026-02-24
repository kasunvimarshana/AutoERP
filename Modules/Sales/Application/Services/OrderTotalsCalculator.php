<?php
namespace Modules\Sales\Application\Services;
class OrderTotalsCalculator
{
    public function calculate(array $lines): array
    {
        $subtotal = '0.00000000';
        $taxTotal = '0.00000000';
        foreach ($lines as &$line) {
            $lineTotal = bcmul((string)$line['qty'], (string)$line['unit_price'], 8);
            $discountPct = isset($line['discount_pct']) ? (string)$line['discount_pct'] : '0';
            $discount = bcmul($lineTotal, bcdiv($discountPct, '100', 8), 8);
            $lineTotal = bcsub($lineTotal, $discount, 8);
            $taxRate = isset($line['tax_rate']) ? (string)$line['tax_rate'] : '0';
            $tax = bcmul($lineTotal, bcdiv($taxRate, '100', 8), 8);
            $line['discount_amount'] = $discount;
            $line['tax_amount'] = $tax;
            $line['line_total'] = $lineTotal;
            $subtotal = bcadd($subtotal, $lineTotal, 8);
            $taxTotal = bcadd($taxTotal, $tax, 8);
        }
        unset($line);
        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => bcadd($subtotal, $taxTotal, 8),
        ];
    }
}
