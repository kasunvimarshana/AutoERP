<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.update') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'locale' => ['sometimes', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'max:60', 'timezone'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
