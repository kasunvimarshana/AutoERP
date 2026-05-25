<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FiscalPeriods;

use Modules\Core\Application\Results\Result;

interface GetFiscalPeriodServiceInterface
{
    public function execute(int|string $id): Result;
}
