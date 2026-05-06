<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Support;

use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

/**
 * Computes tax amounts for sales document lines using active TaxRate records from the Tax module.
 *
 * Compound rates (isCompound = true) are applied on the base of (line_net + simple tax),
 * following standard tax cascading semantics.
 */
final class SalesTaxCalculator
{
    public function __construct(private readonly ?TaxRateRepositoryInterface $taxRateRepository = null) {}

    /**
     * Annotates each line with a computed `tax_amount` and returns the aggregate `tax_total`.
     *
     * If no repository is injected or a line has no `tax_group_id`, the existing `tax_amount`
     * from the line payload is preserved (defaults to 0).
     *
     * @param  list<array<string, mixed>>  $lines     Lines that already have `line_total` set
     * @param  int                         $tenantId
     * @param  \DateTimeInterface          $onDate    Used to filter active rates by validity window
     * @return array{lines: list<array<string, mixed>>, tax_total: string}
     */
    public function calculateForLines(array $lines, int $tenantId, \DateTimeInterface $onDate): array
    {
        $taxTotal = '0.000000';
        $computed = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                $computed[] = $line;
                continue;
            }

            $taxGroupId = isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null;
            $lineNet = $this->decimal($line['line_total'] ?? '0.000000');

            if ($taxGroupId !== null && $this->taxRateRepository !== null) {
                $lineTax = $this->computeTax($lineNet, $tenantId, $taxGroupId, $onDate);
            } else {
                $lineTax = $this->decimal($line['tax_amount'] ?? '0.000000');
            }

            $line['tax_amount'] = $lineTax;
            $taxTotal = bcadd($taxTotal, $lineTax, 6);
            $computed[] = $line;
        }

        return ['lines' => $computed, 'tax_total' => $taxTotal];
    }

    /**
     * Computes the tax for a single line net amount.
     *
     * Simple rates are applied to `$lineNet`.
     * Compound rates are applied to `$lineNet + simpleTax`.
     */
    private function computeTax(
        string $lineNet,
        int $tenantId,
        int $taxGroupId,
        \DateTimeInterface $onDate,
    ): string {
        /** @var list<TaxRate> $rates */
        $rates = $this->taxRateRepository->findActiveByGroup($tenantId, $taxGroupId, $onDate);

        $simpleTax = '0.000000';
        $compoundTax = '0.000000';

        foreach ($rates as $rate) {
            if ($rate->isCompound()) {
                continue;
            }

            $simpleTax = bcadd(
                $simpleTax,
                $this->applyRate($lineNet, $rate),
                6,
            );
        }

        $compoundBase = bcadd($lineNet, $simpleTax, 6);

        foreach ($rates as $rate) {
            if (! $rate->isCompound()) {
                continue;
            }

            $compoundTax = bcadd(
                $compoundTax,
                $this->applyRate($compoundBase, $rate),
                6,
            );
        }

        return bcadd($simpleTax, $compoundTax, 6);
    }

    private function applyRate(string $base, TaxRate $rate): string
    {
        if ($rate->getType() === 'fixed') {
            return $this->decimal($rate->getRate());
        }

        // percentage
        return bcdiv(bcmul($base, $this->decimal($rate->getRate()), 6), '100', 6);
    }

    private function decimal(mixed $value): string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return number_format((float) $value, 6, '.', '');
        }

        return '0.000000';
    }
}
