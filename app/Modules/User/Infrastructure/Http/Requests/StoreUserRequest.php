<?php

declare(strict_types=1);

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
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'email' => 'required|email|unique:users,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|array',
            'preferences' => 'nullable|array',
            'active' => 'boolean',
            'avatar' => 'nullable|string|max:2048',
            'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ];
    }
}
