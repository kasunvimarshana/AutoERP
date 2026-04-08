<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Tax\Application\Contracts\CalculateTaxServiceInterface;
use Modules\Tax\Domain\Entities\TaxGroupRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

class CalculateTaxService implements CalculateTaxServiceInterface
{
    public function __construct(
        private readonly TaxGroupRepositoryInterface $taxGroupRepository,
        private readonly TaxGroupRateRepositoryInterface $taxGroupRateRepository,
    ) {}

    public function calculate(string $tenantId, string $taxGroupId, float $amount): array
    {
        $group = $this->taxGroupRepository->findById($tenantId, $taxGroupId);

        if ($group === null) {
            throw new NotFoundException("TaxGroup [{$taxGroupId}] not found.");
        }

        $rates = $this->taxGroupRateRepository->findByTaxGroup($tenantId, $taxGroupId);
        $activeRates = array_filter($rates, fn(TaxGroupRate $r) => $r->isActive);
        usort($activeRates, fn(TaxGroupRate $a, TaxGroupRate $b) => $a->sequence <=> $b->sequence);

        $breakdown = [];
        $totalTax = 0.0;

        if ($group->isCompound()) {
            $runningBase = $amount;
            foreach ($activeRates as $rate) {
                $taxAmount = $this->applyRate($rate, $runningBase);
                $breakdown[] = [
                    'name'   => $rate->name,
                    'rate'   => $rate->rate,
                    'type'   => $rate->type,
                    'amount' => $taxAmount,
                ];
                $totalTax += $taxAmount;
                $runningBase += $taxAmount;
            }
        } else {
            foreach ($activeRates as $rate) {
                $taxAmount = $this->applyRate($rate, $amount);
                $breakdown[] = [
                    'name'   => $rate->name,
                    'rate'   => $rate->rate,
                    'type'   => $rate->type,
                    'amount' => $taxAmount,
                ];
                $totalTax += $taxAmount;
            }
        }

        return [
            'tax_amount' => $totalTax,
            'breakdown'  => $breakdown,
            'total'      => $amount + $totalTax,
        ];
    }

    private function applyRate(TaxGroupRate $rate, float $base): float
    {
        return match ($rate->type) {
            'percentage' => $base * $rate->rate,
            'fixed'      => $rate->rate,
            default      => 0.0,
        };
    }
}
