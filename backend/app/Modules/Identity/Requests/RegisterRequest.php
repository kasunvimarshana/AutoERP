<?php

namespace App\Modules\Identity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Register Request
 * 
 * Validation rules for user registration
 * 
 * @package App\Modules\Identity\Requests
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'phone' => 'nullable|string|max:20',
            'terms_accepted' => 'required|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your full name.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'tenant_id.required' => 'Tenant identification is required.',
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',
        ];
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => 'active',
        ]);
    }
}
