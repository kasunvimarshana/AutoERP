<?php
declare(strict_types=1);
namespace Modules\Tax\Application\Contracts;

use Modules\Tax\Domain\Entities\TaxCalculationResult;

interface CalculateTaxServiceInterface
{
    /**
     * Calculate tax for a given base amount using the specified tax group.
     * Supports non-compound (simple additive) and compound rates applied in sortOrder sequence.
     */
    public function calculate(int $taxGroupId, float $baseAmount): TaxCalculationResult;
}
