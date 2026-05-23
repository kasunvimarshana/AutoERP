<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUomConversionRequest extends FormRequest
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
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'from_uom_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'to_uom_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'factor' => ['required', 'numeric', 'gt:0'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'is_bidirectional' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
