<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Modules\Accounting\Domain\Entities\ExpenseCategory;

interface ExpenseCategoryRepositoryInterface
{
    public function findById(int $id): ?ExpenseCategory;
    public function findByTenant(int $tenantId): array;
    public function findByCode(int $tenantId, string $code): ?ExpenseCategory;
    public function create(array $data): ExpenseCategory;
    public function update(int $id, array $data): ?ExpenseCategory;
    public function delete(int $id): bool;
}
