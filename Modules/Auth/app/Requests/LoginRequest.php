<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login Request
 * 
 * Validates user login credentials
 */
class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'revoke_other_tokens' => ['nullable', 'boolean'],
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
            'email.required' => __('auth::validation.email_required'),
            'email.email' => __('auth::validation.email_invalid'),
            'password.required' => __('auth::validation.password_required'),
        ];
    }
}
