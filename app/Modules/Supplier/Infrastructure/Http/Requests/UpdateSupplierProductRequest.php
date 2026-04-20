<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'supplier_sku' => 'nullable|string|max:255',
            'lead_time_days' => 'nullable|integer|min:0',
            'min_order_qty' => 'nullable|numeric|gt:0',
            'is_preferred' => 'nullable|boolean',
            'last_purchase_price' => 'nullable|numeric|min:0',
        ];
    }
}
