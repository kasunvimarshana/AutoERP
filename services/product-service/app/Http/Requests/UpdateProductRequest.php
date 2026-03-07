<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['sometimes', 'nullable', 'string'],
            'sku'            => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'price'          => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'category'       => ['sometimes', 'string', 'max:100'],
            'status'         => ['sometimes', 'string', 'in:active,inactive'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'               => 'Product name must not exceed 255 characters.',
            'sku.unique'             => 'A product with this SKU already exists.',
            'price.numeric'          => 'Price must be a valid number.',
            'price.min'              => 'Price must be zero or greater.',
            'status.in'              => 'Status must be either active or inactive.',
            'stock_quantity.integer' => 'Stock quantity must be a whole number.',
            'stock_quantity.min'     => 'Stock quantity must be zero or greater.',
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
