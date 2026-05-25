<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertUomConversionRequest extends FormRequest
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
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'from_uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'to_uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'factor' => array_merge($required, ['numeric']),
            'item_id' => ['nullable', 'integer', 'min:1', 'exists:items,id'],
            'is_bidirectional' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}