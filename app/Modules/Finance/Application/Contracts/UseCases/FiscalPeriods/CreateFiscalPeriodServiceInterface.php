<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FiscalPeriods;

use Modules\Core\Application\Results\Result;

interface CreateFiscalPeriodServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
