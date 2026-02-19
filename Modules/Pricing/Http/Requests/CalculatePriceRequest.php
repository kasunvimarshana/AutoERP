<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CalculatePriceRequest
 *
 * Validates price calculation request
 */
class CalculatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'location_id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'date' => ['nullable', 'date'],
            'context' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Product not found',
            'quantity.required' => 'Quantity is required',
            'quantity.regex' => 'Quantity must be a valid decimal number',
            'location_id.exists' => 'Location not found',
            'date.date' => 'Date must be a valid date',
        ];
    }
}
