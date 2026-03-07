<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'          => 'sometimes|string|max:255',
            'customer_email'         => 'sometimes|email|max:255',
            'shipping_address'       => 'sometimes|array',
            'shipping_address.line1' => 'sometimes|string|max:255',
            'shipping_address.city'  => 'sometimes|string|max:100',
            'shipping_address.state' => 'sometimes|string|max:100',
            'shipping_address.zip'   => 'sometimes|string|max:20',
            'shipping_address.country' => 'sometimes|string|size:2',
            'billing_address'        => 'sometimes|array',
            'notes'                  => 'sometimes|nullable|string|max:1000',
            'metadata'               => 'sometimes|array',
            'shipping_cost'          => 'sometimes|numeric|min:0',
            'discount'               => 'sometimes|numeric|min:0',
        ];
    }
}
