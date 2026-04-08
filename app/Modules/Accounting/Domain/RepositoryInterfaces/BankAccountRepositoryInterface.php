<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\BankAccount;
interface BankAccountRepositoryInterface {
    public function findById(string $tenantId, string $id): ?BankAccount;
    public function findAll(string $tenantId): array;
    public function save(BankAccount $account): void;
    public function delete(string $tenantId, string $id): void;
}
