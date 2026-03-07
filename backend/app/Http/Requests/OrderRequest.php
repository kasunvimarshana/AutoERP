<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_price'        => ['nullable', 'numeric', 'min:0'],
            'currency'                  => ['nullable', 'string', 'size:3'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'discount'                  => ['nullable', 'numeric', 'min:0'],
            'tax'                       => ['nullable', 'numeric', 'min:0'],
            'shipping_address'          => ['nullable', 'array'],
            'shipping_address.street'   => ['nullable', 'string'],
            'shipping_address.city'     => ['nullable', 'string'],
            'shipping_address.state'    => ['nullable', 'string'],
            'shipping_address.zip'      => ['nullable', 'string'],
            'shipping_address.country'  => ['nullable', 'string'],
            'billing_address'           => ['nullable', 'array'],
        ];
    }
}
