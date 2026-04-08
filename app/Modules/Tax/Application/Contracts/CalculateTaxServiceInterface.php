<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Contracts;

interface CalculateTaxServiceInterface
{
    /**
     * Calculate tax for the given amount using the specified tax group.
     *
     * @return array{tax_amount: float, breakdown: array<array{name: string, rate: float, type: string, amount: float}>, total: float}
     */
    public function calculate(string $tenantId, string $taxGroupId, float $amount): array;
}
