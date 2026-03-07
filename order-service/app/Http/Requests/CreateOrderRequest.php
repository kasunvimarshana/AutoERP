<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'          => ['required', 'uuid'],
            'customer_email'       => ['required', 'email', 'max:255'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'uuid'],
            'items.*.quantity'     => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.price'        => ['required', 'numeric', 'min:0'],
            'items.*.product_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer ID is required.',
            'customer_id.uuid'     => 'Customer ID must be a valid UUID.',
            'items.required'       => 'Order must contain at least one item.',
            'items.min'            => 'Order must contain at least one item.',
            'items.*.product_id.required' => 'Each item must have a product ID.',
            'items.*.product_id.uuid'     => 'Each item product ID must be a valid UUID.',
            'items.*.quantity.required'   => 'Each item must have a quantity.',
            'items.*.quantity.integer'    => 'Item quantity must be an integer.',
            'items.*.quantity.min'        => 'Item quantity must be at least 1.',
            'items.*.price.required'      => 'Each item must have a price.',
            'items.*.price.numeric'       => 'Item price must be a number.',
            'items.*.price.min'           => 'Item price cannot be negative.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
