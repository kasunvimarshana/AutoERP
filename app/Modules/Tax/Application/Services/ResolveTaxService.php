<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\ResolveTaxServiceInterface;
use Modules\Tax\Application\DTOs\TaxResolveData;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class ResolveTaxService extends BaseService implements ResolveTaxServiceInterface
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $taxRateRepository,
        private readonly TaxRuleRepositoryInterface $taxRuleRepository,
    ) {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): array
    {
        $dto = TaxResolveData::fromArray($data);
        $onDate = $dto->transaction_date !== null ? new \DateTimeImmutable($dto->transaction_date) : new \DateTimeImmutable;

        $matchedRule = null;
        $taxGroupId = $dto->tax_group_id;

        if ($taxGroupId === null) {
            $matchedRule = $this->taxRuleRepository->findBestMatch(
                tenantId: $dto->tenant_id,
                productCategoryId: $dto->product_category_id,
                partyType: $dto->party_type,
                region: $dto->region,
            );

            $taxGroupId = $matchedRule?->getTaxGroupId();
        }

        if ($taxGroupId === null) {
            return [
                'tax_group_id' => null,
                'matched_rule_id' => null,
                'lines' => [],
                'total_tax_amount' => $this->formatDecimal('0'),
                'total_amount' => $this->formatDecimal($dto->taxable_amount),
            ];
        }

        $rates = $this->taxRateRepository->findActiveByGroup($dto->tenant_id, $taxGroupId, $onDate);
        usort($rates, fn (TaxRate $a, TaxRate $b): int => [$a->isCompound() ? 1 : 0, $a->getId() ?? 0] <=> [$b->isCompound() ? 1 : 0, $b->getId() ?? 0]);

        $baseAmount = (float) $dto->taxable_amount;
        $accumulatedTax = 0.0;
        $lines = [];

        foreach ($rates as $rate) {
            $lineBase = $rate->isCompound() ? $baseAmount + $accumulatedTax : $baseAmount;
            $lineTax = $this->computeTaxAmount($rate, $lineBase);
            $accumulatedTax += $lineTax;

            $lines[] = [
                'tax_rate_id' => $rate->getId(),
                'name' => $rate->getName(),
                'rate' => $rate->getRate(),
                'type' => $rate->getType(),
                'is_compound' => $rate->isCompound(),
                'taxable_amount' => $this->formatDecimal((string) $lineBase),
                'tax_amount' => $this->formatDecimal((string) $lineTax),
                'tax_account_id' => $rate->getAccountId(),
            ];
        }

        return [
            'tax_group_id' => $taxGroupId,
            'matched_rule_id' => $matchedRule?->getId(),
            'lines' => $lines,
            'total_tax_amount' => $this->formatDecimal((string) $accumulatedTax),
            'total_amount' => $this->formatDecimal((string) ($baseAmount + $accumulatedTax)),
        ];
    }

    private function computeTaxAmount(TaxRate $rate, float $lineBase): float
    {
        if ($rate->getType() === 'fixed') {
            return (float) $rate->getRate();
        }

        return $lineBase * ((float) $rate->getRate() / 100);
    }

    private function formatDecimal(string $value): string
    {
        return number_format((float) $value, 6, '.', '');
    }
}
