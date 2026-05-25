<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTaxRuleRequest extends FormRequest
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
            'item_category_id' => ['nullable', 'integer', 'min:1', 'exists:item_categories,id', ],
            'party_type' => ['nullable', 'string', 'max:255', ],
            'region' => ['nullable', 'string', 'max:255', ],
            'priority' => ['nullable', 'integer', 'min:0', ],
            'is_active' => ['nullable', 'boolean', ],
        ];
    }
}
