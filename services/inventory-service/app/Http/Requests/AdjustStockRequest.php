<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'not_in:0'],
            'type'     => ['required', 'string', 'in:in,out,adjustment'],
            'reason'   => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'A quantity (positive or negative) is required.',
            'quantity.not_in'   => 'Quantity cannot be zero.',
            'type.required'     => 'A movement type is required.',
            'type.in'           => 'Type must be one of: in, out, adjustment.',
            'reason.required'   => 'A reason for the adjustment is required.',
        ];
    }
}
