<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Accounting\Domain\Entities\ExpenseCategory;
use Modules\Accounting\Domain\RepositoryInterfaces\ExpenseCategoryRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\ExpenseCategoryModel;

class EloquentExpenseCategoryRepository implements ExpenseCategoryRepositoryInterface
{
    public function __construct(private readonly ExpenseCategoryModel $model) {}

    private function toEntity(ExpenseCategoryModel $m): ExpenseCategory
    {
        return new ExpenseCategory(
            $m->id, $m->tenant_id, $m->name, $m->code,
            $m->parent_id, $m->account_id, (bool)$m->is_active,
            $m->description, $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?ExpenseCategory
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findByCode(int $tenantId, string $code): ?ExpenseCategory
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function create(array $data): ExpenseCategory
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?ExpenseCategory
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
