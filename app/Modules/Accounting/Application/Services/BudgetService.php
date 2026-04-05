<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\BudgetServiceInterface;
use Modules\Accounting\Domain\Entities\Budget;
use Modules\Accounting\Domain\RepositoryInterfaces\BudgetRepositoryInterface;

class BudgetService implements BudgetServiceInterface
{
    public function __construct(private readonly BudgetRepositoryInterface $repo) {}

    public function findById(int $id): Budget
    {
        $budget = $this->repo->findById($id);
        if (!$budget) throw new \RuntimeException("Budget with ID {$id} not found.");
        return $budget;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->repo->findByTenant($tenantId);
    }

    public function create(array $data): Budget
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): Budget
    {
        $budget = $this->repo->update($id, $data);
        if (!$budget) throw new \RuntimeException("Budget with ID {$id} not found.");
        return $budget;
    }

    public function delete(int $id): bool
    {
        $budget = $this->repo->findById($id);
        if (!$budget) throw new \RuntimeException("Budget with ID {$id} not found.");
        return $this->repo->delete($id);
    }
}
