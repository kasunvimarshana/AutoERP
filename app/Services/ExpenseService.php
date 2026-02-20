<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginateCategories(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ExpenseCategory::where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function createCategory(array $data): ExpenseCategory
    {
        return DB::transaction(function () use ($data) {
            $category = ExpenseCategory::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: ExpenseCategory::class,
                auditableId: $category->id,
                newValues: $data
            );

            return $category;
        });
    }

    public function updateCategory(string $id, array $data): ExpenseCategory
    {
        return DB::transaction(function () use ($id, $data) {
            $category = ExpenseCategory::findOrFail($id);
            $oldValues = $category->toArray();
            $category->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: ExpenseCategory::class,
                auditableId: $category->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $category->fresh();
        });
    }

    public function paginateExpenses(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Expense::where('tenant_id', $tenantId)
            ->with(['expenseCategory', 'businessLocation', 'paymentAccount', 'createdBy']);

        if (isset($filters['expense_category_id'])) {
            $query->where('expense_category_id', $filters['expense_category_id']);
        }
        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('expense_date')->paginate($perPage);
    }

    public function createExpense(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $expense = Expense::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Expense::class,
                auditableId: $expense->id,
                newValues: $data
            );

            return $expense->fresh(['expenseCategory', 'paymentAccount']);
        });
    }

    public function updateExpense(string $id, array $data): Expense
    {
        return DB::transaction(function () use ($id, $data) {
            $expense = Expense::findOrFail($id);
            $oldValues = $expense->toArray();
            $expense->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Expense::class,
                auditableId: $expense->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $expense->fresh(['expenseCategory', 'paymentAccount']);
        });
    }

    public function deleteExpense(string $id): void
    {
        Expense::findOrFail($id)->delete();
    }
}
