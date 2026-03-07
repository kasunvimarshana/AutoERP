<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        $tenantId  = App::has('tenant') ? App::make('tenant')->id : null;

        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'sku'         => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)->where('tenant_id', $tenantId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'cost_price'  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'unit'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
