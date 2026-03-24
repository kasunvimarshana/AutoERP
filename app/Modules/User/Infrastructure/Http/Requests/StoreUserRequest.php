<?php

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'    => 'required|integer|exists:tenants,id',
            'email'        => 'required|email|unique:users,email',
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|array',
            'preferences'  => 'nullable|array',
            'active'       => 'boolean',
            'roles'        => 'nullable|array',
            'roles.*'      => 'integer|exists:roles,id',
        ];
    }
}
