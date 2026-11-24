<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\PayrollTaxTable;

final class PayrollTaxService
{
    /**
     * Calculate progressive tax for taxable income.
     */
    public function calculateTax(int $tenantId, string $taxableIncome, int $year): string
    {
        if (bccomp($taxableIncome, '0.0000', 4) <= 0) {
            return '0.0000';
        }

        $taxTable = PayrollTaxTable::query()
            ->with(['brackets' => fn ($q) => $q->orderBy('bracket_order')])
            ->where('tenant_id', $tenantId)
            ->where('effective_year', $year)
            ->where('is_active', true)
            ->first();

        // Fallback to latest active table if specific year table is not defined
        if ($taxTable === null) {
            $taxTable = PayrollTaxTable::query()
                ->with(['brackets' => fn ($q) => $q->orderBy('bracket_order')])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->latest('effective_year')
                ->first();
        }

        if ($taxTable === null || $taxTable->brackets->isEmpty()) {
            return '0.0000';
        }

        // Apply personal relief
        $relief = (string) $taxTable->standard_personal_relief;
        $netTaxable = bcsub($taxableIncome, $relief, 4);

        if (bccomp($netTaxable, '0.0000', 4) <= 0) {
            return '0.0000';
        }

        $totalTax = '0.0000';

        foreach ($taxTable->brackets as $bracket) {
            $from = (string) $bracket->threshold_from;
            $to = $bracket->threshold_to !== null ? (string) $bracket->threshold_to : null;
            $rate = bcdiv((string) $bracket->rate_percentage, '100', 4);

            if (bccomp($netTaxable, $from, 4) <= 0) {
                // Income does not reach this bracket
                continue;
            }

            if ($to !== null) {
                $bracketCap = bcsub($to, $from, 4);
                $taxableInBracket = min((float) bcsub($netTaxable, $from, 4), (float) $bracketCap);
            } else {
                // Unbounded upper bracket
                $taxableInBracket = (float) bcsub($netTaxable, $from, 4);
            }

            if ($taxableInBracket > 0) {
                $bracketTax = bcmul(sprintf('%.4f', $taxableInBracket), $rate, 4);
                $totalTax = bcadd($totalTax, $bracketTax, 4);
            }
        }

        return $totalTax;
    }
}
