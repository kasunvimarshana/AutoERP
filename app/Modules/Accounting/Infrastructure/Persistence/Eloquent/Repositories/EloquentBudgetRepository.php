<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Accounting\Domain\Entities\Budget;
use Modules\Accounting\Domain\RepositoryInterfaces\BudgetRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BudgetModel;

class EloquentBudgetRepository implements BudgetRepositoryInterface
{
    public function __construct(private readonly BudgetModel $model) {}

    private function toEntity(BudgetModel $m): Budget
    {
        return new Budget(
            $m->id, $m->tenant_id, $m->account_id, $m->expense_category_id,
            $m->name, $m->period_start, $m->period_end,
            (float)$m->amount, (float)$m->spent_amount, $m->currency,
            $m->notes, $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?Budget
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('period_start')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findByAccount(int $tenantId, int $accountId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->orderByDesc('period_start')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): Budget
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?Budget
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
