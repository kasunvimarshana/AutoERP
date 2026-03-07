<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'       => ['nullable', 'integer', 'min:1'],
            'sku'               => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-_\.]+$/'],
            'name'              => ['sometimes', 'required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'price'             => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.9999'],
            'cost_price'        => ['nullable', 'numeric', 'min:0', 'max:99999999.9999'],
            'currency'          => ['nullable', 'string', 'size:3'],
            'unit'              => ['nullable', 'string', 'max:50'],
            'weight'            => ['nullable', 'numeric', 'min:0'],
            'dimensions'        => ['nullable', 'array'],
            'dimensions.length' => ['nullable', 'numeric', 'min:0'],
            'dimensions.width'  => ['nullable', 'numeric', 'min:0'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0'],
            'dimensions.unit'   => ['nullable', 'string', 'in:cm,in,mm'],
            'status'            => ['nullable', 'string', 'in:active,inactive,discontinued,draft'],
            'is_active'         => ['nullable', 'boolean'],
            'metadata'          => ['nullable', 'array'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string', 'max:100'],
            'images'            => ['nullable', 'array'],
            'images.*'          => ['string', 'url', 'max:2048'],
        ];
    }
}
