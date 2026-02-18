<?php

namespace Modules\IAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.update');
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', "unique:users,email,{$userId}"],
            'phone' => ['sometimes', 'string', 'max:20'],
            'avatar' => ['sometimes', 'url'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'locale' => ['sometimes', 'string', 'in:en,es,fr,de'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
