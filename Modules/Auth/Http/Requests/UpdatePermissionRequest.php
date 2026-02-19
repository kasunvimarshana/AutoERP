<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * UpdatePermissionRequest
 *
 * Validates permission update requests
 */
class UpdatePermissionRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        $permissionId = $this->route('permission');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('permissions', 'slug')->ignore($permissionId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'resource' => ['sometimes', 'string', 'max:255'],
            'action' => ['sometimes', 'string', 'max:255'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Permission name must be a string',
            'slug.unique' => 'This permission slug is already taken',
            'slug.regex' => 'Permission slug must contain only lowercase letters, numbers, and hyphens',
            'description.max' => 'Description cannot exceed 1000 characters',
            'resource.string' => 'Resource must be a string',
            'action.string' => 'Action must be a string',
            'metadata.array' => 'Metadata must be an array',
        ];
    }
}
