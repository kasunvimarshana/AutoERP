<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
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
            'warehouse_id' => ['nullable', 'integer'],
            'currency_id' => ['nullable', 'integer'],
            'sales_order_id' => ['nullable', 'integer'],
            'carrier' => ['nullable', 'string'],
            'tracking_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.uom_id' => ['nullable', 'integer'],
            'lines.*.from_location_id' => ['nullable', 'integer'],
            'lines.*.shipped_qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.sales_order_line_id' => ['nullable', 'integer'],
            'lines.*.variant_id' => ['nullable', 'integer'],
            'lines.*.batch_id' => ['nullable', 'integer'],
            'lines.*.serial_id' => ['nullable', 'integer'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
