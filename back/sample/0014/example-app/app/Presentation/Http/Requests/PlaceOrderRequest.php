<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add auth::check() or policy gate here in production
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'A customer ID is required to place an order.',
            'customer_id.uuid'     => 'The customer ID must be a valid UUID.',
        ];
    }
}
