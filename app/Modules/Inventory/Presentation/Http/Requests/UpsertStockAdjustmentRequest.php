<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertStockAdjustmentRequest extends FormRequest
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
            'reference_number' => array_merge($required, ['string', 'max:255']),
            'warehouse_id' => array_merge($required, ['integer', 'min:1', 'exists:warehouses,id']),
            'location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'counted_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'counted_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
        ];
    }
}