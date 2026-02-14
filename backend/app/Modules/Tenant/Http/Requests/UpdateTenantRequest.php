<?php

namespace App\Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('tenant');
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:tenants,email,' . $tenantId],
            'phone' => ['nullable', 'string', 'max:20'],
            'plan' => ['sometimes', 'string', 'in:basic,standard,premium,enterprise'],
            'status' => ['sometimes', 'string', 'in:active,suspended,cancelled'],
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
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
        ];
    }
}
