<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTraceLogRequest extends FormRequest
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
            'identifier_id' => ['nullable', 'integer', 'min:1', 'exists:item_identifiers,id'],
            'action_type' => array_merge($required, ['string', 'max:255']),
            'source_warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'source_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'destination_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'quantity' => ['nullable', 'numeric'],
            'performed_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'performed_at' => array_merge($required, ['date']),
            'device_id' => ['nullable', 'string', 'max:255'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer', 'min:0'],
            'entity_type' => array_merge($required, ['string', 'max:255']),
            'entity_id' => array_merge($required, ['integer', 'min:0']),
        ];
    }
}