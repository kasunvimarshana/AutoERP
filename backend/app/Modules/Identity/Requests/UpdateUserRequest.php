<?php

namespace App\Modules\Identity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Update User Request
 * 
 * Validation rules for updating an existing user
 * 
 * @package App\Modules\Identity\Requests
 */
class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('id') ?? $this->route('user');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $userId,
            'password' => ['sometimes', 'nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
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
            'email.unique' => 'This email address is already in use by another user.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->password === null || $this->password === '') {
            $this->request->remove('password');
        }
    }
}
