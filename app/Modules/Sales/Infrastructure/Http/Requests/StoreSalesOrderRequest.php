<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'order_date' => 'required|date',
            'org_unit_id' => 'nullable|integer',
            'price_list_id' => 'nullable|integer',
            'requested_delivery_date' => 'nullable|date',
            'exchange_rate' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'created_by' => 'nullable|integer',
            'lines' => 'nullable|array',
            'lines.*.product_id' => 'required_with:lines|integer',
            'lines.*.uom_id' => 'required_with:lines|integer',
            'lines.*.ordered_qty' => 'required_with:lines|numeric|min:0',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
            'lines.*.variant_id' => 'nullable|integer',
            'lines.*.description' => 'nullable|string',
            'lines.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_group_id' => 'nullable|integer',
            'lines.*.income_account_id' => 'nullable|integer',
        ];
    }
}
