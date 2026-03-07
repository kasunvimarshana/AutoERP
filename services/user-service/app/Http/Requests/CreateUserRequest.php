<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RBAC middleware
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255'],
            'username'    => ['nullable', 'string', 'max:100', 'alpha_dash'],
            'role'        => ['required', 'string', 'in:super-admin,admin,manager,staff,viewer'],
            'status'      => ['nullable', 'string', 'in:active,inactive,suspended'],
            'keycloak_id' => ['nullable', 'string', 'max:255'],
            'profile'     => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'metadata'    => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Role must be one of: super-admin, admin, manager, staff, viewer.',
        ];
    }
}
