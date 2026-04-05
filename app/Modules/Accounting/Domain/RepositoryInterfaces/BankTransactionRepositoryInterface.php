<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Modules\Accounting\Domain\Entities\BankTransaction;

interface BankTransactionRepositoryInterface
{
    public function findById(int $id): ?BankTransaction;
    public function findByBankAccount(int $bankAccountId, int $perPage = 15, int $page = 1): array;
    public function findPendingByTenant(int $tenantId): array;
    public function create(array $data): BankTransaction;
    public function createBatch(array $records): int;
    public function update(int $id, array $data): ?BankTransaction;
    public function updateBatch(array $ids, array $data): int;
    public function delete(int $id): bool;
}
