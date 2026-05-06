<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:accounts,id'],
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'sub_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'is_system' => ['sometimes', 'boolean'],
            'is_bank_account' => ['sometimes', 'boolean'],
            'is_credit_card' => ['sometimes', 'boolean'],
            'currency_id' => ['sometimes', 'nullable', 'integer', 'exists:currencies,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'depth' => ['sometimes', 'integer', 'min:0'],
            'row_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
