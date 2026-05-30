<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'account_group' => ['nullable', 'string', 'max:255'],
            'normal_balance' => ['required', 'string', 'max:255'],
            'is_control_account' => ['nullable', 'boolean'],
            'is_bank_account' => ['nullable', 'boolean'],
            'is_cash_account' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'allows_manual_posting' => ['nullable', 'boolean'],
            'path' => ['nullable', 'string', 'max:255'],
            'depth' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['nullable', 'integer', 'min:1'],
            'updated_by' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
