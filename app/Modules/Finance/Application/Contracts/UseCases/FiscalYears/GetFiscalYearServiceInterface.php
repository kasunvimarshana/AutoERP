<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FiscalYears;

use Modules\Core\Application\Results\Result;

interface GetFiscalYearServiceInterface
{
    public function execute(int|string $id): Result;
}
