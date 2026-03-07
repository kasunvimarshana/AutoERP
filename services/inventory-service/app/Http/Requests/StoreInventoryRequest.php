<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'         => ['required', 'integer', 'min:1'],
            'quantity'           => ['required', 'integer', 'min:0'],
            'reserved_quantity'  => ['sometimes', 'integer', 'min:0'],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'reorder_level'      => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity'   => ['sometimes', 'integer', 'min:1'],
            'unit_cost'          => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'status'             => ['sometimes', 'string', 'in:active,inactive'],
            'last_counted_at'    => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'  => 'Product ID is required.',
            'product_id.integer'   => 'Product ID must be an integer.',
            'product_id.min'       => 'Product ID must be a positive integer.',
            'quantity.required'    => 'Quantity is required.',
            'quantity.integer'     => 'Quantity must be a whole number.',
            'quantity.min'         => 'Quantity must be zero or greater.',
            'unit_cost.numeric'    => 'Unit cost must be a valid number.',
            'unit_cost.min'        => 'Unit cost must be zero or greater.',
            'status.in'            => 'Status must be either active or inactive.',
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
