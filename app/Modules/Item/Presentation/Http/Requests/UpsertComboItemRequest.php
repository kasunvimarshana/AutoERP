<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertComboItemRequest extends FormRequest
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
            'combo_item_id' => array_merge(
                $required,
                ['integer', 'min:1', 'exists:items,id', 'different:component_item_id'],
            ),
            'component_item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'component_variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'quantity' => array_merge($required, ['numeric', 'gt:0']),
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'standard_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
