<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\BankTransaction;

interface CategorizeTransactionServiceInterface
{
    public function execute(int $transactionId, int $categoryId, int $accountId): BankTransaction;
    public function autoApplyRules(int $tenantId): int;
}
