<?php

namespace Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id'    => 'required|uuid',
            'sku'           => 'required|string|max:100',
            'name'          => 'required|string|max:255',
            'attributes'    => 'nullable|array',
            'unit_price'    => 'nullable|numeric|min:0',
            'cost_price'    => 'nullable|numeric|min:0',
            'barcode_ean13' => 'nullable|string|max:13',
            'is_active'     => 'nullable|boolean',
        ];
    }
}
