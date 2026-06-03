<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\Purchase\Application\Contracts\Services\PurchaseAmountCalculatorInterface;

final class PurchaseAmountCalculator implements PurchaseAmountCalculatorInterface
{
    public function __construct(private readonly TaxCalculationServiceInterface $taxCalculationService) {}

    /** @param array<string, mixed> $line */
    public function hydrateLineTotals(array $line, float $quantity, float $unitPrice): array
    {
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountAmount = $this->resolveDiscountAmount(
            $grossAmount,
            (string) ($line['discount_type'] ?? ''),
            round((float) ($line['discount_value'] ?? 0), 4),
        );
        $lineTotal = round(max(0.0, $grossAmount - $discountAmount), 4);
        $taxAmount = $this->resolveTaxAmount(
            (int) ($line['tenant_id'] ?? 0),
            isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
            $lineTotal,
            $line['posting_date'] ?? null,
        );

        $line['gross_amount'] = $grossAmount;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = $taxAmount;
        $line['line_total'] = $lineTotal;
        $line['line_total_with_tax'] = round($lineTotal + $taxAmount, 4);

        return $line;
    }

    public function resolveDiscountAmount(float $grossAmount, string $discountType, float $discountValue): float
    {
        if ($discountValue <= 0) {
            return 0.0;
        }

        if (strtolower(trim($discountType)) === 'percentage') {
            return round(min($grossAmount, ($grossAmount * $discountValue) / 100), 4);
        }

        return round(min($grossAmount, $discountValue), 4);
    }

    public function resolveTaxAmount(int $tenantId, ?int $taxGroupId, float $taxableAmount, mixed $postingDate = null): float
    {
        if ($tenantId < 1 || $taxGroupId === null || $taxGroupId < 1 || $taxableAmount <= 0) {
            return 0.0;
        }

        $result = $this->taxCalculationService->calculate(
            $tenantId,
            $taxGroupId,
            $taxableAmount,
            $postingDate !== null ? (string) $postingDate : null,
        );

        if ($result->isFailure()) {
            return 0.0;
        }

        $tax = $result->valueOrFail();

        return round((float) ($tax['tax_amount'] ?? 0), 4);
    }

    public function resolveHeaderDiscountAmount(DataRecord $header, float $discountableAmount): float
    {
        return $this->resolveDiscountAmount(
            max(0.0, $discountableAmount),
            (string) $header->get('header_discount_type', ''),
            round((float) $header->get('header_discount_value', 0), 4),
        );
    }
}
