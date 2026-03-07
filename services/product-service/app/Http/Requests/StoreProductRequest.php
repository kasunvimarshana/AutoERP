<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'sku'            => ['required', 'string', 'max:100', 'unique:products,sku'],
            'price'          => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'category'       => ['required', 'string', 'max:100'],
            'status'         => ['required', 'string', 'in:active,inactive'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Product name is required.',
            'name.max'                => 'Product name must not exceed 255 characters.',
            'sku.required'            => 'SKU is required.',
            'sku.unique'              => 'A product with this SKU already exists.',
            'price.required'          => 'Price is required.',
            'price.numeric'           => 'Price must be a valid number.',
            'price.min'               => 'Price must be zero or greater.',
            'category.required'       => 'Category is required.',
            'status.required'         => 'Status is required.',
            'status.in'               => 'Status must be either active or inactive.',
            'stock_quantity.required' => 'Stock quantity is required.',
            'stock_quantity.integer'  => 'Stock quantity must be a whole number.',
            'stock_quantity.min'      => 'Stock quantity must be zero or greater.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
