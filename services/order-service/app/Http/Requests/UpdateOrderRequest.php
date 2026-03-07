<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'email', 'max:255'],

            'shipping_address'                      => ['sometimes', 'array'],
            'shipping_address.street'               => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.city'                 => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.state'                => ['sometimes', 'string', 'max:100'],
            'shipping_address.postal_code'          => ['required_with:shipping_address', 'string', 'max:20'],
            'shipping_address.country'              => ['required_with:shipping_address', 'string', 'max:100'],

            'billing_address'                       => ['sometimes', 'array'],
            'billing_address.street'                => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.city'                  => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.state'                 => ['sometimes', 'string', 'max:100'],
            'billing_address.postal_code'           => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.country'               => ['required_with:billing_address', 'string', 'max:100'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_email.email'                       => 'Customer email must be a valid email address.',
            'shipping_address.street.required_with'      => 'Shipping street is required when shipping address is provided.',
            'shipping_address.city.required_with'        => 'Shipping city is required when shipping address is provided.',
            'shipping_address.postal_code.required_with' => 'Shipping postal code is required when shipping address is provided.',
            'shipping_address.country.required_with'     => 'Shipping country is required when shipping address is provided.',
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
