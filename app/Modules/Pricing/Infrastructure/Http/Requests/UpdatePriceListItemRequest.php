<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePriceListItemRequest extends FormRequest
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
            'uom_id' => 'required|integer|exists:units_of_measure,id',
            'min_quantity' => 'nullable|numeric|gt:0',
            'price' => 'required|numeric|min:0',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'row_version' => 'required|integer|min:1',
        ];
    }
}
