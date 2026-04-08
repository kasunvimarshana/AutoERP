<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\Account;
interface AccountServiceInterface {
    public function createAccount(string $tenantId, array $data): Account;
    public function updateAccount(string $tenantId, string $id, array $data): Account;
    public function deleteAccount(string $tenantId, string $id): void;
    public function getAccount(string $tenantId, string $id): Account;
    public function getAllAccounts(string $tenantId): array;
    public function getAccountsByType(string $tenantId, string $type): array;
}
