<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'currency_id' => ['nullable', 'integer'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'order_date' => ['nullable', 'date'],
            'created_by' => ['nullable', 'integer'],
            'expected_date' => ['nullable', 'date'],
            'org_unit_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'string'],
            'tax_total' => ['nullable', 'string'],
            'discount_total' => ['nullable', 'string'],
            'grand_total' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'approved_by' => ['nullable', 'integer'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['required_with:lines', 'integer'],
            'lines.*.uom_id' => ['required_with:lines', 'integer'],
            'lines.*.ordered_qty' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.variant_id' => ['nullable', 'integer'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_group_id' => ['nullable', 'integer'],
            'lines.*.account_id' => ['nullable', 'integer'],
            'metadata.discount_strategy' => ['nullable', 'string', 'in:unit,total,hybrid,basket'],
            'metadata.discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'metadata.discount_value' => ['nullable', 'numeric', 'min:0'],
            'metadata.stack_discounts' => ['nullable', 'boolean'],
        ];
    }
}
