<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTransferOrderLineRequest extends FormRequest
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
            'transfer_order_id' => [...$required, 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'from_location_id' => ['nullable', 'integer', 'min:1'],
            'to_location_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => [...$required, 'integer', 'min:1'],
            'requested_qty' => [...$required, 'numeric', 'gt:0'],
            'shipped_qty' => ['nullable', 'numeric', 'gte:0'],
            'received_qty' => ['nullable', 'numeric', 'gte:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
