<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertStockAdjustmentRequest extends FormRequest
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
            'reference_number' => [...$required, 'string', 'max:100'],
            'warehouse_id' => [...$required, 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'counted_by' => ['nullable', 'integer', 'min:1'],
            'counted_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'integer', 'min:1'],
            'approved_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'lines' => ['sometimes', 'array'],
            'lines.*.item_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.location_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.direction' => ['nullable', 'string', 'max:20'],
            'lines.*.current_quantity' => ['nullable', 'numeric'],
            'lines.*.adjustment_quantity' => ['required_with:lines', 'numeric', 'not_in:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.reason_code' => ['nullable', 'string', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }
}
