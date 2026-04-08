<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\BudgetServiceInterface;
use Modules\Accounting\Domain\Entities\Budget;
use Modules\Accounting\Domain\RepositoryInterfaces\BudgetRepositoryInterface;
class BudgetService implements BudgetServiceInterface
{
    public function __construct(
        private readonly BudgetRepositoryInterface $budgetRepository,
    ) {}
    public function getBudget(string $tenantId, string $id): Budget
    {
        $budget = $this->budgetRepository->findById($tenantId, $id);
        if ($budget === null) {
            throw new NotFoundException("Budget [{$id}] not found.");
        }
        return $budget;
    }
    public function createBudget(string $tenantId, array $data): Budget
    {
        return DB::transaction(function () use ($tenantId, $data): Budget {
            $now = now();
            $budget = new Budget(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                fiscalYear: (int) $data['fiscal_year'],
                startDate: new \DateTimeImmutable($data['start_date']),
                endDate: new \DateTimeImmutable($data['end_date']),
                status: $data['status'] ?? 'draft',
                totalAmount: (float) ($data['total_amount'] ?? 0.0),
                notes: $data['notes'] ?? null,
                createdAt: $now,
                updatedAt: $now,
            );
            $this->budgetRepository->save($budget);
            return $budget;
        });
    }
    public function updateBudget(string $tenantId, string $id, array $data): Budget
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Budget {
            $existing = $this->getBudget($tenantId, $id);
            $updated = new Budget(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                fiscalYear: (int) ($data['fiscal_year'] ?? $existing->fiscalYear),
                startDate: isset($data['start_date']) ? new \DateTimeImmutable($data['start_date']) : $existing->startDate,
                endDate: isset($data['end_date']) ? new \DateTimeImmutable($data['end_date']) : $existing->endDate,
                status: $data['status'] ?? $existing->status,
                totalAmount: (float) ($data['total_amount'] ?? $existing->totalAmount),
                notes: $data['notes'] ?? $existing->notes,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->budgetRepository->save($updated);
            return $updated;
        });
    }
    public function deleteBudget(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getBudget($tenantId, $id);
            $this->budgetRepository->delete($tenantId, $id);
        });
    }
    public function getAllBudgets(string $tenantId): array
    {
        return $this->budgetRepository->findAll($tenantId);
    }
}
