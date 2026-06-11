<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertTaxRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tax_type' => ['required', 'string', 'max:100'],
            'calculation_method' => ['required', Rule::in(config('tax.calculation_methods', []))],
            'is_withholding' => ['nullable', 'boolean'],
            'recoverable' => ['nullable', 'boolean'],
            'payable' => ['nullable', 'boolean'],
            'receivable' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
