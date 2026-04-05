<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Modules\Accounting\Domain\Entities\Budget;

interface BudgetRepositoryInterface
{
    public function findById(int $id): ?Budget;
    public function findByTenant(int $tenantId): array;
    public function findByAccount(int $tenantId, int $accountId): array;
    public function create(array $data): Budget;
    public function update(int $id, array $data): ?Budget;
    public function delete(int $id): bool;
}
