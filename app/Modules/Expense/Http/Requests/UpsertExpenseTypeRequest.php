<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertExpenseTypeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'row_version' => [$this->isMethod('PUT') ? 'required' : 'prohibited', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
