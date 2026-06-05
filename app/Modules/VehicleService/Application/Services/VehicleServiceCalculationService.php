<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;

final class VehicleServiceCalculationService
{
    public function __construct(private readonly TaxCalculationServiceInterface $taxCalculationService)
    {
    }

    public function calculateLine(array $line, bool $includeIncentive = false): array
    {
        $quantity = round((float) ($line['quantity'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountAmount = $this->discountAmount(
            $grossAmount,
            (string) ($line['discount_type'] ?? ''),
            round((float) ($line['discount_value'] ?? 0), 4),
        );
        $lineTotal = round(max(0.0, $grossAmount - $discountAmount), 4);
        $taxAmount = $this->taxAmount(
            (int) ($line['tenant_id'] ?? 0),
            isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
            $lineTotal,
            $line['posting_date'] ?? null,
        );

        $line['quantity'] = $quantity;
        $line['unit_price'] = $unitPrice;
        $line['gross_amount'] = $grossAmount;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = $taxAmount;
        $line['line_total'] = $lineTotal;
        $line['line_total_with_tax'] = round($lineTotal + $taxAmount, 4);

        if ($includeIncentive) {
            $line['incentive_amount'] = $this->discountAmount(
                $lineTotal,
                (string) ($line['incentive_type'] ?? ''),
                round((float) ($line['incentive_value'] ?? 0), 4),
            );
        }

        return $line;
    }

    public function calculateExternalService(array $line): array
    {
        $quantity = round((float) ($line['quantity'] ?? 1), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountAmount = $this->discountAmount(
            $grossAmount,
            (string) ($line['discount_type'] ?? ''),
            round((float) ($line['discount_value'] ?? 0), 4),
        );

        $line['quantity'] = $quantity;
        $line['unit_price'] = $unitPrice;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = 0.0;
        $line['line_total'] = $grossAmount;

        return $line;
    }

    public function discountAmount(float $grossAmount, string $discountType, float $discountValue): float
    {
        if ($discountValue <= 0) {
            return 0.0;
        }

        if (strtolower(trim($discountType)) === 'percentage') {
            return round(min($grossAmount, ($grossAmount * $discountValue) / 100), 4);
        }

        return round(min($grossAmount, $discountValue), 4);
    }

    public function taxAmount(int $tenantId, ?int $taxGroupId, float $taxableAmount, mixed $postingDate = null): float
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
}
