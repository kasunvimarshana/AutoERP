<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankAccounts;

use Modules\Core\Application\Results\Result;

interface CreateBankAccountServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
