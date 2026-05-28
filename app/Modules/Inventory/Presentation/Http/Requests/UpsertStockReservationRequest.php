<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertStockReservationRequest extends FormRequest
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
            'tenant_id' => [...$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => [...$required, 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => [...$required, 'integer', 'min:1'],
            'quantity' => [...$required, 'numeric', 'gt:0'],
            'expires_at' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'reserved_for_type' => ['nullable', 'string', 'max:255'],
            'reserved_for_id' => ['nullable', 'integer', 'min:1'],
            'reserved_by' => ['nullable', 'integer', 'min:1'],
            'condition' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
