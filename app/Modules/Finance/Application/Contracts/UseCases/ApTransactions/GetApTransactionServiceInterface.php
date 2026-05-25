<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\ApTransactions;

use Modules\Core\Application\Results\Result;

interface GetApTransactionServiceInterface
{
    public function execute(int|string $id): Result;
}
