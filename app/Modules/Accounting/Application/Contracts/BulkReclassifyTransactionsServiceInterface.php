<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;

interface BulkReclassifyTransactionsServiceInterface
{
    public function execute(array $transactionIds, int $categoryId, int $accountId): int;
}
