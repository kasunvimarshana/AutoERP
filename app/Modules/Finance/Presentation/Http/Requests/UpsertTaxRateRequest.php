<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTaxRateRequest extends FormRequest
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
            'tax_group_id' => ['required', 'integer', 'min:1', 'exists:tax_groups,id', ],
            'name' => ['required', 'string', 'max:255', ],
            'type' => ['nullable', 'string', 'max:255', ],
            'rate' => ['required', 'numeric', ],
            'account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id', ],
            'is_compound' => ['nullable', 'boolean', ],
            'is_active' => ['nullable', 'boolean', ],
            'valid_from' => ['nullable', 'date', ],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
        ];
    }
}
