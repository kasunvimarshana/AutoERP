<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'        => ['required', 'uuid'],
            'warehouse_id'      => ['required', 'uuid'],
            'quantity'          => ['required', 'integer', 'min:0'],
            'reserved_quantity' => ['sometimes', 'integer', 'min:0'],
            'unit_cost'         => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'   => 'A product ID is required.',
            'warehouse_id.required' => 'A warehouse ID is required.',
            'quantity.required'     => 'An initial quantity is required.',
            'quantity.min'          => 'Quantity cannot be negative.',
        ];
    }
}
