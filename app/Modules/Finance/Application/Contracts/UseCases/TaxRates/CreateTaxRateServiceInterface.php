<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\TaxRates;

use Modules\Core\Application\Results\Result;

interface CreateTaxRateServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
