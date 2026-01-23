<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Update User Request
 * 
 * Validates data for updating an existing user
 */
class UpdateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password' => ['sometimes', 'required', 'string', 'confirmed', Password::defaults()],
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
            'name.required' => __('user::validation.name.required'),
            'email.required' => __('user::validation.email.required'),
            'email.email' => __('user::validation.email.email'),
            'email.unique' => __('user::validation.email.unique'),
            'password.required' => __('user::validation.password.required'),
            'password.confirmed' => __('user::validation.password.confirmed'),
        ];
    }
}
