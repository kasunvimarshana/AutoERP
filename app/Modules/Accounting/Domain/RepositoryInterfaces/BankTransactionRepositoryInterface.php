<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\BankTransaction;
interface BankTransactionRepositoryInterface {
    public function findById(string $tenantId, string $id): ?BankTransaction;
    public function findByBankAccount(string $tenantId, string $bankAccountId, array $filters = []): array;
    public function findByReference(string $tenantId, string $bankAccountId, string $reference): ?BankTransaction;
    public function save(BankTransaction $tx): void;
    public function delete(string $tenantId, string $id): void;
}
