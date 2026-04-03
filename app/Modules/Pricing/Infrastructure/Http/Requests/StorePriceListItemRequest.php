<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'       => 'required|integer',
            'price_list_id'   => 'required|integer',
            'product_id'      => 'required|integer',
            'variation_id'    => 'nullable|integer',
            'unit_price'      => 'required|numeric|min:0',
            'min_quantity'    => 'required|numeric|min:0',
            'max_quantity'    => 'nullable|numeric|min:0',
            'discount_percent'=> 'required|numeric|min:0|max:100',
            'markup_percent'  => 'required|numeric|min:0',
            'currency_code'   => 'required|string|size:3',
            'uom_code'        => 'nullable|string|max:50',
            'is_active'       => 'boolean',
            'metadata'        => 'nullable|array',
        ];
    }
}
