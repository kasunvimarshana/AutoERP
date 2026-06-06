<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertUomConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'from_uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'to_uom_id' => array_merge($required, [
                'integer',
                'min:1',
                'exists:unit_of_measures,id',
                'different:from_uom_id',
            ]),
            'factor' => array_merge($required, ['numeric', 'gt:0']),
            'category' => ['nullable', 'string', 'max:50'],
            'item_id' => ['nullable', 'integer', 'min:1', 'exists:items,id'],
            'is_bidirectional' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
