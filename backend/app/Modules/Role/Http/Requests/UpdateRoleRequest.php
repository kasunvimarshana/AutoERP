<?php

namespace App\Modules\Role\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');
        
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'tenant_id' => ['sometimes', 'string', 'exists:tenants,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This role name already exists',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'permissions.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}
