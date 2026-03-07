<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'min:8', 'confirmed'],
            'role'      => ['required', 'in:admin,manager,staff'],
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
        ];
    }
}
