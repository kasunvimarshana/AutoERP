<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\ArTransactions;

use Modules\Core\Application\Results\Result;

interface GetArTransactionServiceInterface
{
    public function execute(int|string $id): Result;
}
