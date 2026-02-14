<?php

namespace App\Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'unique:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'plan' => ['nullable', 'string', 'in:basic,standard,premium,enterprise'],
            'domain' => ['required', 'string', 'unique:domains,domain'],
            'settings' => ['nullable', 'array'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Tenant ID is required',
            'id.unique' => 'This tenant ID is already taken',
            'name.required' => 'Tenant name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'domain.required' => 'Domain is required',
            'domain.unique' => 'This domain is already taken',
        ];
    }
}
