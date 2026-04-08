<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\BankTransaction;
interface BankTransactionServiceInterface {
    public function createTransaction(string $tenantId, array $data): BankTransaction;
    public function updateTransaction(string $tenantId, string $id, array $data): BankTransaction;
    public function deleteTransaction(string $tenantId, string $id): void;
    public function getTransaction(string $tenantId, string $id): BankTransaction;
    public function getTransactions(string $tenantId, string $bankAccountId, array $filters = []): array;
}
