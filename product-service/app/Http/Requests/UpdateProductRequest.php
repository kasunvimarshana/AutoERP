<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateProductRequest
 *
 * Validates the request data for updating an existing product.
 * All fields are optional (PATCH semantics supported via PUT).
 */
class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock'       => ['sometimes', 'required', 'integer', 'min:0'],
            'sku'         => ['nullable', 'string', 'max:100', "unique:products,sku,{$productId}"],
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
            'price.min'      => 'Product price cannot be negative.',
            'stock.integer'  => 'Product stock must be a whole number.',
            'sku.unique'     => 'This SKU is already in use.',
        ];
    }
}
