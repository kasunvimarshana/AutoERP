<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankAccounts;

use Modules\Core\Application\Results\Result;

interface DeleteBankAccountServiceInterface
{
    public function execute(int|string $id): Result;
}
