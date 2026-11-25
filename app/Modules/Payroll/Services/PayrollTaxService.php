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

use Modules\Core\Services\DecimalMath;
use Modules\Payroll\Models\PayrollTaxTable;

final class PayrollTaxService
{
    public function __construct(
        private readonly DecimalMath $math,
    ) {}

    /**
     * Calculate progressive tax for taxable income.
     */
    public function calculateTax(int $tenantId, string $taxableIncome, int $year): string
    {
        $zero = $this->math->normalize('0', DecimalMath::SCALE);
        if ($this->math->compare($taxableIncome, $zero, DecimalMath::SCALE) <= 0) {
            return $zero;
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
            return $zero;
        }

        // Apply personal relief
        $relief = (string) $taxTable->standard_personal_relief;
        $netTaxable = $this->math->sub($taxableIncome, $relief, DecimalMath::SCALE);

        if ($this->math->compare($netTaxable, $zero, DecimalMath::SCALE) <= 0) {
            return $zero;
        }

        $totalTax = $zero;

        foreach ($taxTable->brackets as $bracket) {
            $from = (string) $bracket->threshold_from;
            $to = $bracket->threshold_to !== null ? (string) $bracket->threshold_to : null;
            $rate = $this->math->div((string) $bracket->rate_percentage, '100', DecimalMath::SCALE);

            if ($this->math->compare($netTaxable, $from, DecimalMath::SCALE) <= 0) {
                // Income does not reach this bracket
                continue;
            }

            $remaining = $this->math->sub($netTaxable, $from, DecimalMath::SCALE);
            if ($to !== null) {
                $bracketCap = $this->math->sub($to, $from, DecimalMath::SCALE);
                $taxableInBracket = $this->math->compare($remaining, $bracketCap, DecimalMath::SCALE) <= 0
                    ? $remaining
                    : $bracketCap;
            } else {
                // Unbounded upper bracket
                $taxableInBracket = $remaining;
            }

            if ($this->math->compare($taxableInBracket, $zero, DecimalMath::SCALE) > 0) {
                $bracketTax = $this->math->mul($taxableInBracket, $rate, DecimalMath::SCALE);
                $totalTax = $this->math->add($totalTax, $bracketTax, DecimalMath::SCALE);
            }
        }

        return $totalTax;
    }
}
