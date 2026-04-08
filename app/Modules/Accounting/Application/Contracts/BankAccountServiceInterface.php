<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\BankAccount;
interface BankAccountServiceInterface {
    public function createBankAccount(string $tenantId, array $data): BankAccount;
    public function updateBankAccount(string $tenantId, string $id, array $data): BankAccount;
    public function deleteBankAccount(string $tenantId, string $id): void;
    public function getBankAccount(string $tenantId, string $id): BankAccount;
    public function getAllBankAccounts(string $tenantId): array;
}
