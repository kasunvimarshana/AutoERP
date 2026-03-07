<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'email'       => ['sometimes', 'email', 'max:255'],
            'username'    => ['nullable', 'string', 'max:100', 'alpha_dash'],
            'role'        => ['sometimes', 'string', 'in:super-admin,admin,manager,staff,viewer'],
            'status'      => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'keycloak_id' => ['nullable', 'string', 'max:255'],
            'profile'     => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
