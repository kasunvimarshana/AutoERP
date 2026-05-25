<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankReconciliations;

use Modules\Core\Application\Results\Result;

interface GetBankReconciliationServiceInterface
{
    public function execute(int|string $id): Result;
}
