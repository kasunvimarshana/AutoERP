<?php

namespace Modules\Budget\Infrastructure\Repositories;

use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Infrastructure\Models\BudgetLineModel;

class BudgetLineRepository implements BudgetLineRepositoryInterface
{
    public function findByBudget(string $budgetId): iterable
    {
        return BudgetLineModel::where('budget_id', $budgetId)->get();
    }

    public function findById(string $id): ?object
    {
        return BudgetLineModel::find($id);
    }

    public function create(array $data): object
    {
        return BudgetLineModel::create($data);
    }

    public function addActualAmount(string $id, string $amount): object
    {
        $line = BudgetLineModel::findOrFail($id);
        $line->actual_amount = bcadd((string) $line->actual_amount, $amount, 8);
        $line->save();

        return $line->fresh();
    }

    public function deleteByBudget(string $budgetId): void
    {
        BudgetLineModel::where('budget_id', $budgetId)->delete();
    }
}
