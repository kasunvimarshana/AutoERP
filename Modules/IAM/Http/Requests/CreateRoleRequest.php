<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Role Request
 *
 * Validation for creating a new role.
 */
class CreateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-role');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'slug.unique' => 'This role slug already exists',
            'parent_id.exists' => 'Invalid parent role',
            'permission_ids.*.exists' => 'One or more permissions do not exist',
        ];
    }
}
