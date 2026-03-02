<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Contracts;
use Modules\Accounting\Domain\Entities\Account;
interface AccountRepositoryInterface {
    public function findById(int $id, int $tenantId): ?Account;
    public function findByCode(string $code, int $tenantId): ?Account;
    public function findAll(int $tenantId): array;
    /** @return Account[] — active accounts for the given types, ordered by code */
    public function findActiveByTypes(array $types, int $tenantId): array;
    public function save(Account $account): Account;
    public function delete(int $id, int $tenantId): void;
}

