<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\Budget;
interface BudgetRepositoryInterface {
    public function findById(string $tenantId, string $id): ?Budget;
    public function findAll(string $tenantId): array;
    public function findActive(string $tenantId): array;
    public function save(Budget $budget): void;
    public function delete(string $tenantId, string $id): void;
}
