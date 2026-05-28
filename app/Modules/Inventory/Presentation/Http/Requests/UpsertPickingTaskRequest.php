<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPickingTaskRequest extends FormRequest
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
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_line_id' => ['nullable', 'integer', 'min:1'],
            'stock_reservation_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'source_warehouse_id' => [...$required, 'integer', 'min:1'],
            'source_location_id' => ['nullable', 'integer', 'min:1'],
            'transaction_uom_id' => [...$required, 'integer', 'min:1'],
            'reserved_quantity' => [...$required, 'numeric', 'gte:0'],
            'picked_quantity' => ['nullable', 'numeric', 'gte:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'assigned_user_id' => ['nullable', 'integer', 'min:1'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
