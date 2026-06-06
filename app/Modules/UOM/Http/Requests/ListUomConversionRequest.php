<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListUomConversionRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('uom.pagination.max_per_page', 200)],
            'from_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'to_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
