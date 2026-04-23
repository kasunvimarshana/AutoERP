<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComboItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'combo_product_id' => 'required|integer|exists:products,id',
            'component_product_id' => 'required|integer|exists:products,id',
            'component_variant_id' => 'nullable|integer',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'required|integer',
            'metadata' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'is_optional' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ];
    }
}
