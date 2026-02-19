<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateRoleRequest
 *
 * Validates role update requests
 */
class UpdateRoleRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Role name must be a string',
            'slug.unique' => 'This role slug is already taken',
            'slug.regex' => 'Role slug must contain only lowercase letters, numbers, and hyphens',
            'description.max' => 'Description cannot exceed 1000 characters',
            'metadata.array' => 'Metadata must be an array',
        ];
    }
}
