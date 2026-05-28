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
            'tenant_id' => [...$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'serial_number' => [...$required, 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'current_location_id' => ['nullable', 'integer', 'min:1'],
            'current_owner_type' => ['nullable', 'string', 'max:100'],
            'current_owner_id' => ['nullable', 'integer', 'min:1'],
            'warranty_expiry' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'manufacture_date' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
