<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AllocateInventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'organization_unit_id' => ['sometimes', 'nullable', 'integer'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer'],
            'location_id' => ['sometimes', 'nullable', 'integer'],
            'item_id' => ['required', 'integer'],
            'variant_id' => ['sometimes', 'nullable', 'integer'],
            'batch_id' => ['sometimes', 'nullable', 'integer'],
            'serial_id' => ['sometimes', 'nullable', 'integer'],
            'uom_id' => ['sometimes', 'nullable', 'integer'],
            'lot_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'allocation_method' => ['sometimes', 'nullable', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'dimensions' => ['sometimes', 'array'],
        ];
    }
}
