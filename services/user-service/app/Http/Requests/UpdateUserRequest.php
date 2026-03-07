<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'email'              => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'password'           => ['sometimes', 'string', 'min:8', 'confirmed'],
            'is_active'          => ['sometimes', 'boolean'],
            'role_ids'           => ['nullable', 'array'],
            'role_ids.*'         => ['integer', 'exists:roles,id'],
            'permission_ids'     => ['nullable', 'array'],
            'permission_ids.*'   => ['integer', 'exists:permissions,id'],
            'metadata'           => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'    => 'This email is already taken by another user.',
            'password.min'    => 'Password must be at least 8 characters.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
