<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListTraceLogRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('inventory.pagination.max_per_page', 200)],
            'identifier_id' => ['nullable', 'integer', 'min:1'],
            'action_type' => ['nullable', 'string', 'max:255'],
            'source_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'destination_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'source_location_id' => ['nullable', 'integer', 'min:1'],
            'destination_location_id' => ['nullable', 'integer', 'min:1'],
            'performed_by' => ['nullable', 'integer', 'min:1'],
            'performed_at' => ['nullable', 'date'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string', 'max:255'],
        ];
    }
}