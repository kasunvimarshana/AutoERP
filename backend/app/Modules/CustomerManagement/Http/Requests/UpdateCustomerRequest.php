<?php

namespace App\Modules\CustomerManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'customer_type' => 'sometimes|in:individual,business',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => "sometimes|email|unique:customers,email,{$customerId}",
            'phone' => 'sometimes|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'id_number' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'tax_id' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:active,inactive,blocked',
            'preferred_language' => 'nullable|string|max:5',
        ];
    }
}
