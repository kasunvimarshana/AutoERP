<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * StorePermissionRequest
 *
 * Validates permission creation requests
 */
class StorePermissionRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:permissions,slug', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'resource' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:255'],
            'metadata' => ['sometimes', 'array'],
            'is_system' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required',
            'slug.required' => 'Permission slug is required',
            'slug.unique' => 'This permission slug is already taken',
            'slug.regex' => 'Permission slug must contain only lowercase letters, numbers, and hyphens',
            'description.max' => 'Description cannot exceed 1000 characters',
            'resource.required' => 'Resource is required',
            'action.required' => 'Action is required',
            'metadata.array' => 'Metadata must be an array',
            'is_system.boolean' => 'System flag must be true or false',
        ];
    }
}
