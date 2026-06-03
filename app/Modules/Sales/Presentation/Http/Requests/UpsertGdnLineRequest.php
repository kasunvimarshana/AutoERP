<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertGdnLineRequest extends FormRequest
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
            'reference' => ['nullable', 'string', 'max:255'],
            'gdn_header_id' => [...$required, 'integer', 'min:1'],
            'sales_order_line_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'uom_id' => [...$required, 'integer', 'min:1'],
            'expected_qty' => [...$required, 'numeric', 'gt:0'],
            'picked_qty' => ['nullable', 'numeric', 'min:0'],
            'delivered_qty' => ['nullable', 'numeric', 'min:0'],
            'delivered_base_qty' => ['nullable', 'numeric', 'min:0'],
            'short_qty' => ['nullable', 'numeric', 'min:0'],
            'rejected_qty' => ['nullable', 'numeric', 'min:0'],
            'invoiced_qty' => ['nullable', 'numeric', 'min:0'],
            'returned_qty' => ['nullable', 'numeric', 'min:0'],
            'picking_status' => ['sometimes', 'string', 'max:255'],
            'delivery_status' => ['sometimes', 'string', 'max:255'],
            'unit_price' => [...$required, 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'account_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
