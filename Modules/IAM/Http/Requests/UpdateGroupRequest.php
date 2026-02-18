<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Group Request
 *
 * Validates data for updating an existing group.
 */
class UpdateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('group');
        return $this->user()->can('update', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $groupId = $this->route('group');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('groups', 'slug')->ignore($groupId),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:groups,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Group name is required',
            'slug.unique' => 'This group slug already exists',
            'slug.alpha_dash' => 'Group slug can only contain letters, numbers, dashes and underscores',
            'parent_id.exists' => 'The selected parent group does not exist',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'group name',
            'slug' => 'group slug',
            'description' => 'description',
            'parent_id' => 'parent group',
            'is_active' => 'active status',
        ];
    }
}
