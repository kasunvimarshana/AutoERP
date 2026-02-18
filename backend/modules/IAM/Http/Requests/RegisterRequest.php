<?php

namespace Modules\IAM\Http\Requests;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'phone' => ['sometimes', 'string', 'max:20'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'locale' => ['sometimes', 'string', 'in:en,es,fr,de'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'email.unique' => 'This email is already registered',
        ];
    }
}
