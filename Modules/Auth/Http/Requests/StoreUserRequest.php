<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * StoreUserRequest
 *
 * Validates user creation requests
 */
class StoreUserRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'organization_id.uuid' => 'Organization ID must be a valid UUID',
            'organization_id.exists' => 'The selected organization does not exist',
            'is_active.boolean' => 'Active status must be true or false',
            'metadata.array' => 'Metadata must be an array',
            'role_ids.array' => 'Role IDs must be an array',
            'role_ids.*.uuid' => 'Each role ID must be a valid UUID',
            'role_ids.*.exists' => 'One or more selected roles do not exist',
            'permission_ids.array' => 'Permission IDs must be an array',
            'permission_ids.*.uuid' => 'Each permission ID must be a valid UUID',
            'permission_ids.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}
