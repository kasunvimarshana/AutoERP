<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Reset Password Request
 * 
 * Validates password reset with token
 */
class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
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
            'token.required' => __('auth::validation.token_required'),
            'email.required' => __('auth::validation.email_required'),
            'email.email' => __('auth::validation.email_invalid'),
            'email.exists' => __('auth::validation.email_not_found'),
            'password.required' => __('auth::validation.password_required'),
            'password.confirmed' => __('auth::validation.password_confirmed'),
        ];
    }
}
