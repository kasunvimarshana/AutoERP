<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankTransactions;

use Modules\Core\Application\Results\Result;

interface GetBankTransactionServiceInterface
{
    public function execute(int|string $id): Result;
}
