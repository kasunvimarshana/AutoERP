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
            'tenant_id' => [...$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'entity_type' => [...$required, 'string', 'max:100'],
            'entity_id' => [...$required, 'integer', 'min:1'],
            'identifier_id' => ['nullable', 'integer', 'min:1'],
            'action_type' => [...$required, 'string', 'max:100'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'source_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'destination_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'source_location_id' => ['nullable', 'integer', 'min:1'],
            'destination_location_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['nullable', 'numeric', 'gte:0'],
            'performed_by' => ['nullable', 'integer', 'min:1'],
            'performed_at' => ['nullable', 'date'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
