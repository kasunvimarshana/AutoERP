<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'locale' => ['sometimes', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'max:60', 'timezone'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
