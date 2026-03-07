<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreProductRequest
 *
 * Validates the request data for creating a new product.
 */
class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'sku'         => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'category'    => ['nullable', 'string', 'max:100'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'  => 'Product name is required.',
            'price.required' => 'Product price is required.',
            'price.min'      => 'Product price cannot be negative.',
            'stock.required' => 'Product stock is required.',
            'stock.integer'  => 'Product stock must be a whole number.',
            'sku.unique'     => 'This SKU is already in use.',
        ];
    }
}
