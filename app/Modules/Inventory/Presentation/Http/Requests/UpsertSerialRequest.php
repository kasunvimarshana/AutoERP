<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSerialRequest extends FormRequest
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
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'serial_number' => array_merge($required, ['string', 'max:255']),
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'status' => ['nullable', 'string', 'max:255'],
            'current_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'warranty_expiry' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'manufacture_date' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'numeric'],
            'current_owner_type' => ['nullable', 'string', 'max:255'],
            'current_owner_id' => ['nullable', 'integer', 'min:0'],
        ];
    }
}