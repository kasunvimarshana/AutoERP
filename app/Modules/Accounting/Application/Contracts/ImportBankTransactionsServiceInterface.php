<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;

interface ImportBankTransactionsServiceInterface
{
    public function execute(int $bankAccountId, array $transactions): int;
}
