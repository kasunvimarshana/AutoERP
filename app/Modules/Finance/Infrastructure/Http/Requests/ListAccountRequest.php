<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAccountRequest extends FormRequest
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
            'tenant_id' => ['sometimes', 'integer', 'exists:tenants,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:accounts,id'],
            'code' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:asset,liability,equity,revenue,expense'],
            'sub_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'normal_balance' => ['sometimes', 'in:debit,credit'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
