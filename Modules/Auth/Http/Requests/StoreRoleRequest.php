<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * StoreRoleRequest
 *
 * Validates role creation requests
 */
class StoreRoleRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['sometimes', 'array'],
            'is_system' => ['sometimes', 'boolean'],
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
            'name.required' => 'Role name is required',
            'slug.required' => 'Role slug is required',
            'slug.unique' => 'This role slug is already taken',
            'slug.regex' => 'Role slug must contain only lowercase letters, numbers, and hyphens',
            'description.max' => 'Description cannot exceed 1000 characters',
            'metadata.array' => 'Metadata must be an array',
            'is_system.boolean' => 'System flag must be true or false',
            'permission_ids.array' => 'Permission IDs must be an array',
            'permission_ids.*.uuid' => 'Each permission ID must be a valid UUID',
            'permission_ids.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}
