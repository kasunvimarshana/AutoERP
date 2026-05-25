<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FiscalYears;

use Modules\Core\Application\Results\Result;

interface CreateFiscalYearServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
