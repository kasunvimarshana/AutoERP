<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Customer information
            'customer_id'    => ['sometimes', 'string', 'max:255'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],

            // Financial
            'tax_amount'      => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],

            // Addresses
            'shipping_address'                 => ['required', 'array'],
            'shipping_address.street'          => ['required', 'string', 'max:255'],
            'shipping_address.city'            => ['required', 'string', 'max:100'],
            'shipping_address.state'           => ['sometimes', 'string', 'max:100'],
            'shipping_address.postal_code'     => ['required', 'string', 'max:20'],
            'shipping_address.country'         => ['required', 'string', 'max:100'],

            'billing_address'                  => ['sometimes', 'array'],
            'billing_address.street'           => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.city'             => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.state'            => ['sometimes', 'string', 'max:100'],
            'billing_address.postal_code'      => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.country'          => ['required_with:billing_address', 'string', 'max:100'],

            // Notes
            'notes' => ['nullable', 'string', 'max:1000'],

            // Order items
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'min:1'],
            'items.*.product_name'   => ['required', 'string', 'max:255'],
            'items.*.product_sku'    => ['nullable', 'string', 'max:100'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'           => 'Customer name is required.',
            'customer_email.required'          => 'Customer email is required.',
            'customer_email.email'             => 'Customer email must be a valid email address.',
            'shipping_address.required'        => 'Shipping address is required.',
            'shipping_address.street.required' => 'Shipping street is required.',
            'shipping_address.city.required'   => 'Shipping city is required.',
            'shipping_address.postal_code.required' => 'Shipping postal code is required.',
            'shipping_address.country.required' => 'Shipping country is required.',
            'items.required'                   => 'At least one order item is required.',
            'items.min'                        => 'At least one order item is required.',
            'items.*.product_id.required'      => 'Each item must have a product ID.',
            'items.*.product_name.required'    => 'Each item must have a product name.',
            'items.*.quantity.required'        => 'Each item must have a quantity.',
            'items.*.quantity.min'             => 'Item quantity must be at least 1.',
            'items.*.unit_price.required'      => 'Each item must have a unit price.',
            'items.*.unit_price.min'           => 'Item unit price must be zero or greater.',
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
