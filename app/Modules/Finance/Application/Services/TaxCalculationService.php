<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\Finance\Application\Repositories\TaxRateRepositoryInterface;

final class TaxCalculationService implements TaxCalculationServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRates)
    {
    }

    public function calculate(
        int $tenantId,
        int $taxGroupId,
        float $taxableAmount,
        ?string $postingDate = null,
    ): Result {
        $effectiveDate = CarbonImmutable::parse($postingDate ?? now()->toDateString())->toDateString();

        $rates = $this->taxRates->list([
            'tenant_id' => $tenantId,
            'tax_group_id' => $taxGroupId,
            'is_active' => true,
        ]);

        if ($rates === []) {
            return Result::success([
                'taxable_amount' => round($taxableAmount, 4),
                'tax_amount' => 0.0,
                'total_amount' => round($taxableAmount, 4),
                'details' => [],
            ]);
        }

        $details = [];
        $runningTax = 0.0;

        foreach ($rates as $rate) {
            $validFrom = $rate->get('valid_from');
            $validTo = $rate->get('valid_to');

            if ($validFrom !== null && (string) $validFrom > $effectiveDate) {
                continue;
            }

            if ($validTo !== null && (string) $validTo < $effectiveDate) {
                continue;
            }

            $rateType = strtoupper((string) $rate->get('type', 'PERCENTAGE'));
            $rateValue = (float) $rate->get('rate', 0);
            $isCompound = (bool) $rate->get('is_compound', false);

            $base = $isCompound ? ($taxableAmount + $runningTax) : $taxableAmount;
            $lineTax = $rateType === 'FIXED'
                ? $rateValue
                : (($base * $rateValue) / 100);

            $runningTax += $lineTax;

            $details[] = [
                'tax_rate_id' => (int) $rate->id(),
                'name' => (string) $rate->get('name', 'Tax'),
                'type' => $rateType,
                'rate' => $rateValue,
                'is_compound' => $isCompound,
                'tax_amount' => round($lineTax, 4),
            ];
        }

        return Result::success([
            'taxable_amount' => round($taxableAmount, 4),
            'tax_amount' => round($runningTax, 4),
            'total_amount' => round($taxableAmount + $runningTax, 4),
            'details' => $details,
        ]);
    }
}
