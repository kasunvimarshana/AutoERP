<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'            => 'required|string|max:36',
            'customer_name'          => 'required|string|max:255',
            'customer_email'         => 'required|email|max:255',
            'currency'               => 'sometimes|string|size:3',
            'shipping_address'       => 'required|array',
            'shipping_address.line1' => 'required|string|max:255',
            'shipping_address.city'  => 'required|string|max:100',
            'shipping_address.state' => 'sometimes|string|max:100',
            'shipping_address.zip'   => 'required|string|max:20',
            'shipping_address.country' => 'required|string|size:2',
            'billing_address'        => 'sometimes|array',
            'payment_method'         => 'required|string|in:card,bank_transfer,paypal,stripe,cash',
            'notes'                  => 'sometimes|nullable|string|max:1000',
            'metadata'               => 'sometimes|array',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|string|max:36',
            'items.*.product_name'   => 'required|string|max:255',
            'items.*.product_sku'    => 'required|string|max:100',
            'items.*.quantity'       => 'required|integer|min:1|max:9999',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount'       => 'sometimes|numeric|min:0',
            'items.*.tax'            => 'sometimes|numeric|min:0',
            'items.*.attributes'     => 'sometimes|array',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'             => 'An order must contain at least one item.',
            'items.min'                  => 'An order must contain at least one item.',
            'items.*.product_id.required' => 'Each item must specify a product_id.',
            'items.*.quantity.min'       => 'Item quantity must be at least 1.',
            'items.*.unit_price.min'     => 'Item unit price must not be negative.',
        ];
    }
}
