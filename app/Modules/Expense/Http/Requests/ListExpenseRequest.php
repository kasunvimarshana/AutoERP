<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Expense\Enums\ExpenseStatus;

final class ListExpenseRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'expense_type_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(ExpenseStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
