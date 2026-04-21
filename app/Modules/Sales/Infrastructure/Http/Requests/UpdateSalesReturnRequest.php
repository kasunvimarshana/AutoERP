<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesReturnRequest extends FormRequest
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
            'return_date' => ['nullable', 'date'],
            'original_sales_order_id' => ['nullable', 'integer'],
            'original_invoice_id' => ['nullable', 'integer'],
            'return_reason' => ['nullable', 'string'],
            'exchange_rate' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.uom_id' => ['nullable', 'integer'],
            'lines.*.to_location_id' => ['nullable', 'integer'],
            'lines.*.return_qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.original_sales_order_line_id' => ['nullable', 'integer'],
            'lines.*.variant_id' => ['nullable', 'integer'],
            'lines.*.batch_id' => ['nullable', 'integer'],
            'lines.*.serial_id' => ['nullable', 'integer'],
            'lines.*.condition' => ['nullable', 'string', 'in:new,used,damaged,refurbished'],
            'lines.*.disposition' => ['nullable', 'string'],
            'lines.*.restocking_fee' => ['nullable', 'numeric', 'min:0'],
            'lines.*.quality_check_notes' => ['nullable', 'string'],
        ];
    }
}
