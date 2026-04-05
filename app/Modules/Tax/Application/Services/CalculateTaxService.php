<?php
declare(strict_types=1);
namespace Modules\Tax\Application\Services;

use Modules\Tax\Application\Contracts\CalculateTaxServiceInterface;
use Modules\Tax\Domain\Entities\TaxCalculationResult;
use Modules\Tax\Domain\Entities\TaxGroupRate;
use Modules\Tax\Domain\Exceptions\TaxGroupNotFoundException;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;

class CalculateTaxService implements CalculateTaxServiceInterface
{
    public function __construct(
        private readonly TaxGroupRepositoryInterface $groupRepository,
        private readonly TaxGroupRateRepositoryInterface $rateRepository,
    ) {}

    public function calculate(int $taxGroupId, float $baseAmount): TaxCalculationResult
    {
        $group = $this->groupRepository->findById($taxGroupId);
        if ($group === null) {
            throw new TaxGroupNotFoundException($taxGroupId);
        }

        /** @var TaxGroupRate[] $rates */
        $rates = $this->rateRepository->findByTaxGroup($taxGroupId);

        $breakdown = [];
        $accumulatedTax = 0.0;
        $totalTax = 0.0;

        foreach ($rates as $rate) {
            $tax = $rate->isCompound()
                ? $rate->calculateCompound($baseAmount, $accumulatedTax)
                : $rate->calculate($baseAmount);

            $breakdown[] = [
                'code'  => $rate->getTaxRateCode(),
                'name'  => $rate->getTaxRateName(),
                'rate'  => $rate->getRate(),
                'tax'   => round($tax, 6),
            ];

            $accumulatedTax += $tax;
            $totalTax += $tax;
        }

        return new TaxCalculationResult(
            taxGroupId:   $taxGroupId,
            taxGroupCode: $group->getCode(),
            baseAmount:   $baseAmount,
            totalTax:     round($totalTax, 6),
            breakdown:    $breakdown,
        );
    }
}
