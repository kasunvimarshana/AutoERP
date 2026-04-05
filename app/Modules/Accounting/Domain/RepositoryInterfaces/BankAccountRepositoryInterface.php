<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Modules\Accounting\Domain\Entities\BankAccount;

interface BankAccountRepositoryInterface
{
    public function findById(int $id): ?BankAccount;
    public function findByTenant(int $tenantId): array;
    public function create(array $data): BankAccount;
    public function update(int $id, array $data): ?BankAccount;
    public function delete(int $id): bool;
}
