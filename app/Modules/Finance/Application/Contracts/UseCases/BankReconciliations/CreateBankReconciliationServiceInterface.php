<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankReconciliations;

use Modules\Core\Application\Results\Result;

interface CreateBankReconciliationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
