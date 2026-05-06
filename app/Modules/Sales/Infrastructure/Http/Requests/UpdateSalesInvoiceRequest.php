<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'currency_id' => ['nullable', 'integer'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'sales_order_id' => ['nullable', 'integer'],
            'shipment_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'numeric'],
            'ar_account_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.discount_strategy' => ['nullable', 'string', 'in:unit,total,hybrid,basket'],
            'metadata.discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'metadata.discount_value' => ['nullable', 'numeric', 'min:0'],
            'metadata.stack_discounts' => ['nullable', 'boolean'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.uom_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.sales_order_line_id' => ['nullable', 'integer'],
            'lines.*.variant_id' => ['nullable', 'integer'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_group_id' => ['nullable', 'integer'],
            'lines.*.income_account_id' => ['nullable', 'integer'],
        ];
    }
}
