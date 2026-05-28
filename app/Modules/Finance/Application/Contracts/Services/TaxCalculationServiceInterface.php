<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface TaxCalculationServiceInterface
{
    /**
     * @return Result<array<string, mixed>>
     */
    public function calculate(
        int $tenantId,
        int $taxGroupId,
        float $taxableAmount,
        ?string $postingDate = null,
    ): Result;
}
