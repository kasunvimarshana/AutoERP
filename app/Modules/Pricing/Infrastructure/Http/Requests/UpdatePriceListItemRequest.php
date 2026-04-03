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
            'product_id'      => 'sometimes|required|integer',
            'variation_id'    => 'nullable|integer',
            'unit_price'      => 'sometimes|required|numeric|min:0',
            'min_quantity'    => 'sometimes|required|numeric|min:0',
            'max_quantity'    => 'nullable|numeric|min:0',
            'discount_percent'=> 'nullable|numeric|min:0|max:100',
            'markup_percent'  => 'nullable|numeric|min:0',
            'currency_code'   => 'sometimes|required|string|size:3',
            'uom_code'        => 'nullable|string|max:50',
            'is_active'       => 'nullable|boolean',
            'metadata'        => 'nullable|array',
        ];
    }
}
