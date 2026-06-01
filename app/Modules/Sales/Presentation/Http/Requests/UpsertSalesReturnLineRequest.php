<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSalesReturnLineRequest extends FormRequest
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
            'sales_return_id' => [...$required, 'integer', 'min:1'],
            'original_gdn_line_id' => ['nullable', 'integer', 'min:1'],
            'original_sales_order_line_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'uom_id' => [...$required, 'integer', 'min:1'],
            'return_qty' => [...$required, 'numeric', 'gt:0'],
            'return_base_qty' => ['nullable', 'numeric', 'min:0'],
            'restock_qty' => ['nullable', 'numeric', 'min:0'],
            'scrap_qty' => ['nullable', 'numeric', 'min:0'],
            'refund_qty' => ['nullable', 'numeric', 'min:0'],
            'write_off_qty' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => [...$required, 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'restocking_fee' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:255'],
            'disposition' => ['nullable', 'string', 'max:255'],
            'quality_check_notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'account_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
