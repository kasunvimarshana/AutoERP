<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FiscalPeriods;

use Modules\Core\Application\Results\Result;

interface UpdateFiscalPeriodServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
