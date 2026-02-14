<?php

namespace App\Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'branch_id' => ['required', 'string', 'exists:organizations,id'],
            'code' => ['required', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['required', 'string', 'in:individual,business'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'branch_id.required' => 'Branch ID is required',
            'branch_id.exists' => 'The selected branch does not exist',
            'code.required' => 'Customer code is required',
            'code.unique' => 'This customer code is already taken',
            'name.required' => 'Customer name is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'customer_type.required' => 'Customer type is required',
            'customer_type.in' => 'Customer type must be either individual or business',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
