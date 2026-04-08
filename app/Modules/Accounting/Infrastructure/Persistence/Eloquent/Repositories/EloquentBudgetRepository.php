<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\Budget;
use Modules\Accounting\Domain\RepositoryInterfaces\BudgetRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BudgetModel;
class EloquentBudgetRepository implements BudgetRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Budget
    {
        $model = BudgetModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findAll(string $tenantId): array
    {
        return BudgetModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(BudgetModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function findActive(string $tenantId): array
    {
        return BudgetModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->map(fn(BudgetModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function save(Budget $budget): void
    {
        /** @var BudgetModel $model */
        $model = BudgetModel::withoutGlobalScopes()->findOrNew($budget->id);
        $model->fill([
            'tenant_id'    => $budget->tenantId,
            'name'         => $budget->name,
            'fiscal_year'  => $budget->fiscalYear,
            'start_date'   => $budget->startDate->format('Y-m-d'),
            'end_date'     => $budget->endDate->format('Y-m-d'),
            'status'       => $budget->status,
            'total_amount' => $budget->totalAmount,
            'notes'        => $budget->notes,
        ]);
        if (! $model->exists) {
            $model->id = $budget->id;
        }
        $model->save();
    }
    public function delete(string $tenantId, string $id): void
    {
        BudgetModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }
    private function mapToEntity(BudgetModel $model): Budget
    {
        return new Budget(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            name: (string) $model->name,
            fiscalYear: (int) $model->fiscal_year,
            startDate: $model->start_date ?? now(),
            endDate: $model->end_date ?? now(),
            status: (string) $model->status,
            totalAmount: (float) $model->total_amount,
            notes: $model->notes !== null ? (string) $model->notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
