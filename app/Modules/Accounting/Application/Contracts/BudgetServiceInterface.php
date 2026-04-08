<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\Budget;
interface BudgetServiceInterface {
    public function createBudget(string $tenantId, array $data): Budget;
    public function updateBudget(string $tenantId, string $id, array $data): Budget;
    public function deleteBudget(string $tenantId, string $id): void;
    public function getBudget(string $tenantId, string $id): Budget;
    public function getAllBudgets(string $tenantId): array;
}
