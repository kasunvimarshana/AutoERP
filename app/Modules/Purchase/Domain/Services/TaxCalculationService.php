<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;

final class TaxCalculationService
{
    public function calculateLineTax(float $taxableAmount, ?int $taxGroupId): float
    {
        if ($taxGroupId === null || $taxableAmount <= 0) {
            return 0.0;
        }

        $rates = DB::table('tax_rates')
            ->where('tax_group_id', $taxGroupId)
            ->where('is_active', true)
            ->orderBy('is_compound')
            ->orderBy('id')
            ->get();

        $base = $taxableAmount;
        $tax = 0.0;

        foreach ($rates as $rate) {
            $amount = strtoupper((string) $rate->type) === 'FIXED'
                ? (float) $rate->rate
                : $base * ((float) $rate->rate / 100);

            $tax += $amount;
            if ((bool) $rate->is_compound) {
                $base += $amount;
            }
        }

        return round($tax, 4);
    }
}
