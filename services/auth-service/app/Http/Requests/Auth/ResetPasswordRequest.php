<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'     => ['required', 'string'],
            'email'     => ['required', 'string', 'email', 'max:255'],
            'password'  => [
                'required', 'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'tenant_id' => ['sometimes', 'nullable', 'string', 'max:36'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'    => 'Password reset token is required.',
            'email.required'    => 'Email address is required.',
            'password.confirmed'=> 'Password confirmation does not match.',
        ];
    }
}
