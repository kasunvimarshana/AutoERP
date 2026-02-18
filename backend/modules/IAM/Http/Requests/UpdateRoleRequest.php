<?php

namespace Modules\IAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('role.update');
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => ['sometimes', 'string', 'max:255', "unique:roles,name,{$roleId}"],
            'description' => ['sometimes', 'string', 'max:500'],
            'parent_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
