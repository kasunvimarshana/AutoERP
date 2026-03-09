<?php

namespace App\Http\Requests\Auth;

use App\Domain\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'    => ['required', 'string', 'max:36', 'exists:tenants,id'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => [
                'required', 'string', 'email', 'max:255',
                'unique:users,email',
            ],
            'password'     => [
                'required', 'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'phone'        => ['sometimes', 'nullable', 'string', 'max:30'],
            'org_id'       => ['sometimes', 'nullable', 'string', 'max:36', 'exists:organizations,id'],
            'timezone'     => ['sometimes', 'nullable', 'string', 'timezone'],
            'locale'       => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant identifier is required for registration.',
            'tenant_id.exists'   => 'The specified tenant does not exist.',
            'email.unique'       => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
