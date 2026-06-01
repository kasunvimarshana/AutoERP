<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\Services;

use Modules\Core\Application\DTO\DataRecord;

interface SalesAmountCalculatorInterface
{
    /** @param array<string, mixed> $line */
    public function hydrateLineTotals(array $line, float $quantity, float $unitPrice): array;

    public function resolveDiscountAmount(float $grossAmount, string $discountType, float $discountValue): float;

    public function resolveTaxAmount(int $tenantId, ?int $taxGroupId, float $taxableAmount, mixed $postingDate = null): float;

    public function resolveHeaderDiscountAmount(DataRecord $header, float $discountableAmount): float;
}
