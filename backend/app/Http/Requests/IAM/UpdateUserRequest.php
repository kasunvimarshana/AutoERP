<?php

declare(strict_types=1);

namespace App\Http\Requests\IAM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit-users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'language_code' => ['nullable', 'string', 'size:2'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
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
            'name.required' => 'User name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.confirmed' => 'Password confirmation does not match',
            'organization_id.exists' => 'Selected organization does not exist',
            'branch_id.exists' => 'Selected branch does not exist',
            'timezone.timezone' => 'Please provide a valid timezone',
            'status.in' => 'Status must be active, inactive, or suspended',
            'roles.*.exists' => 'One or more selected roles do not exist',
            'permissions.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}
