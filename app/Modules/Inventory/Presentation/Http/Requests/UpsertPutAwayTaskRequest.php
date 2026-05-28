<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPutAwayTaskRequest extends FormRequest
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
            'receipt_inspection_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_line_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'from_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'from_location_id' => ['nullable', 'integer', 'min:1'],
            'target_warehouse_id' => [...$required, 'integer', 'min:1'],
            'target_location_id' => ['nullable', 'integer', 'min:1'],
            'transaction_uom_id' => [...$required, 'integer', 'min:1'],
            'quantity' => [...$required, 'numeric', 'gt:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'assigned_user_id' => ['nullable', 'integer', 'min:1'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
