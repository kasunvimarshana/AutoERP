<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = App::has('tenant') ? App::make('tenant')->id : null;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['required', 'string', 'max:100', "unique:products,sku,NULL,id,tenant_id,{$tenantId}"],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'category'    => ['nullable', 'string', 'max:100'],
            'unit'        => ['nullable', 'string', 'max:50'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
