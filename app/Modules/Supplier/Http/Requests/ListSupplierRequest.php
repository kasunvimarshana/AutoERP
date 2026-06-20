<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;

final class ListSupplierRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(SupplierStatus::class)],
            'supplier_type' => ['nullable', Rule::enum(SupplierType::class)],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'is_credit_allowed' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['supplier_number', 'code', 'name', 'status', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
