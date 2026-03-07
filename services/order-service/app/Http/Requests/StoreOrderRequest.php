<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'customer_id'              => ['required'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'string'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'total_amount'             => ['required', 'numeric', 'min:0'],
            'currency'                 => ['sometimes', 'string', 'size:3'],
            'metadata'                 => ['sometimes', 'array'],
            'payment_method'           => ['sometimes', 'array'],
            'payment_method.type'      => ['required_with:payment_method', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'At least one order item is required.',
            'items.min'                   => 'At least one order item is required.',
            'items.*.product_id.required' => 'Each item must include a product_id.',
            'items.*.quantity.required'   => 'Each item must include a quantity.',
            'items.*.unit_price.required' => 'Each item must include a unit_price.',
            'total_amount.required'       => 'The total amount is required.',
        ];
    }
}
