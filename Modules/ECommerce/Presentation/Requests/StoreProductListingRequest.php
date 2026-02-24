<?php

namespace Modules\ECommerce\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductListingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'inventory_product_id' => 'nullable|string|uuid',
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'price'                => 'required|numeric|min:0',
            'compare_at_price'     => 'nullable|numeric|min:0',
            'sku'                  => 'nullable|string|max:100',
            'is_published'         => 'boolean',
            'stock_quantity'       => 'nullable|integer|min:0',
            'image_url'            => 'nullable|url|max:2048',
            'tags'                 => 'nullable|array',
            'tags.*'               => 'string',
        ];
    }
}
