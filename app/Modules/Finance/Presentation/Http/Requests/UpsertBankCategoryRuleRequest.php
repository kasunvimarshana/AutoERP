<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertBankCategoryRuleRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'bank_account_id' => ['nullable', 'integer', 'min:1', 'exists:bank_accounts,id', ],
            'name' => ['required', 'string', 'max:255', ],
            'priority' => ['nullable', 'integer', 'min:0', ],
            'match_type' => ['nullable', 'string', 'max:255', ],
            'match_value' => ['required', 'string', 'max:255', ],
            'conditions' => ['nullable', 'array', ],
            'account_id' => ['required', 'integer', 'min:1', 'exists:accounts,id', ],
            'description_template' => ['nullable', 'string', 'max:255', ],
            'is_active' => ['nullable', 'boolean', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
        ];
    }
}
