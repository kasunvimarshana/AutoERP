<?php

namespace App\Modules\Role\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'name.unique' => 'This role name already exists',
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'permissions.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}
