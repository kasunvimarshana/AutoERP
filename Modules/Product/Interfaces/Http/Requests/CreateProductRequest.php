<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'sku'           => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9\-]{0,49}$/i'],
            'type'          => ['required', 'string', 'in:single,variable,combo,service'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id'      => ['nullable', 'integer', 'exists:brands,id'],
            'unit_id'       => ['nullable', 'integer', 'exists:units,id'],
            'description'   => ['nullable', 'string'],
            'barcode'       => ['nullable', 'string', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}
