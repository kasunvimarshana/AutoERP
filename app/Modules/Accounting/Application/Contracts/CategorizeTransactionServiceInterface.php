<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\BankTransaction;
interface CategorizeTransactionServiceInterface {
    public function categorize(string $tenantId, string $transactionId, string $accountId): BankTransaction;
    public function autoCategorize(string $tenantId, string $bankAccountId): int;
}
