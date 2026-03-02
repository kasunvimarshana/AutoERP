<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'adjustment_type' => ['required', 'string', 'in:adjustment_in,adjustment_out'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
