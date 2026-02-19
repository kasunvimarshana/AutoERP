<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Register Request
 *
 * Validates user registration data
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => ['nullable', 'string', 'exists:roles,name'],
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
            'name.required' => __('auth::validation.name_required'),
            'email.required' => __('auth::validation.email_required'),
            'email.email' => __('auth::validation.email_invalid'),
            'email.unique' => __('auth::validation.email_exists'),
            'password.required' => __('auth::validation.password_required'),
            'password.confirmed' => __('auth::validation.password_confirmed'),
        ];
    }
}
