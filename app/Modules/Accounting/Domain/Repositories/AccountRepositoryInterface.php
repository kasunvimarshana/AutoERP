<?php
namespace Modules\Accounting\Domain\Repositories;

use Modules\Accounting\Domain\Entities\Account;

interface AccountRepositoryInterface
{
    public function findById(int $id): ?Account;
    public function findByCode(int $tenantId, string $code): ?Account;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): Account;
    public function update(Account $account, array $data): Account;
    public function delete(Account $account): bool;
}
