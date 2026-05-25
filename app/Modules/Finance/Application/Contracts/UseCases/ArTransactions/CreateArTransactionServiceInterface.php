<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\ArTransactions;

use Modules\Core\Application\Results\Result;

interface CreateArTransactionServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
