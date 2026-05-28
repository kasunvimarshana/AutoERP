<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertReceiptInspectionRequest extends FormRequest
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
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'transaction_uom_id' => [...$required, 'integer', 'min:1'],
            'received_quantity' => [...$required, 'numeric', 'gte:0'],
            'accepted_quantity' => ['nullable', 'numeric', 'gte:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'gte:0'],
            'damaged_quantity' => ['nullable', 'numeric', 'gte:0'],
            'inspection_status' => ['nullable', 'string', 'max:50'],
            'inspected_by' => ['nullable', 'integer', 'min:1'],
            'inspected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
