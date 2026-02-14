<?php

namespace App\Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'tenant_id' => ['sometimes', 'string', 'exists:tenants,id'],
            'branch_id' => ['sometimes', 'string', 'exists:organizations,id'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($customerId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['sometimes', 'string', 'in:individual,business'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist',
            'branch_id.exists' => 'The selected branch does not exist',
            'code.unique' => 'This customer code is already taken',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'customer_type.in' => 'Customer type must be either individual or business',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
