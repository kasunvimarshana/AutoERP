<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductSupplierPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'supplier_id' => 'required|integer',
            'currency_id' => 'nullable|integer',
            'uom_id' => 'required|integer',
            'min_order_quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
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
