<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankCategoryRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'bank_account_id' => ['sometimes', 'nullable', 'integer', 'exists:bank_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'conditions' => ['required', 'array'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description_template' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
