<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Group Request
 *
 * Validates data for creating a new group.
 */
class StoreGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\IAM\Entities\Group::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:groups,slug', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:groups,id'],
            'is_active' => ['boolean'],
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
