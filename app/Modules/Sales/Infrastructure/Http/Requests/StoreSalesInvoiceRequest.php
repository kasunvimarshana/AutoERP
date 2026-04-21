<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'sales_order_id' => ['nullable', 'integer'],
            'shipment_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'numeric'],
            'ar_account_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.uom_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.sales_order_line_id' => ['nullable', 'integer'],
            'lines.*.variant_id' => ['nullable', 'integer'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_group_id' => ['nullable', 'integer'],
            'lines.*.income_account_id' => ['nullable', 'integer'],
        ];
    }
}
