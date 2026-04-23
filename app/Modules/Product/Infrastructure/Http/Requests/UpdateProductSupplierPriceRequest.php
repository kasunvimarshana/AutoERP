<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductSupplierPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'supplier_id' => 'sometimes|integer',
            'currency_id' => 'nullable|integer',
            'uom_id' => 'sometimes|integer',
            'min_order_quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'lead_time_days' => 'nullable|integer|min:0',
            'is_preferred' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
