<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\BankAccount;

interface BankAccountServiceInterface
{
    public function findById(int $id): BankAccount;
    public function findByTenant(int $tenantId): array;
    public function create(array $data): BankAccount;
    public function update(int $id, array $data): BankAccount;
    public function delete(int $id): bool;
}
